# GCP Deployment Guide (Low Cost POC)

This guide describes how to deploy the application to Google Cloud Platform using a **cost-optimized serverless architecture** with custom domains.

## Architecture Highlights
- **Pay-as-you-go**: Both API and Frontend use Cloud Run (scales to zero).
- **No Load Balancer**: Saves ~$18/month. On GCP, serving a static site from Cloud Storage with **HTTPS on a custom domain** requires a Global Load Balancer. Cloud Run "Domain Mapping" gives you managed SSL and custom domains for $0.
- **Free SSL**: Google manages SSL certificates for the custom domains mapped to Cloud Run.

## Prerequisites
1.  **Google Cloud SDK**: Authenticated (`gcloud auth login`).
2.  **Terraform**: Installed.
3.  **Docker**: Installed.

## Step 1: Infrastructure (Terraform)
1.  Navigate to `terraform/`.
2.  Configure `terraform.tfvars`:
    ```hcl
    project_id    = "your-project-id"
    region        = "asia-southeast1"
    db_password   = "secure-password"
    app_key       = "base64:..." 
    
    # Mailer Config
    mail_password = "your-smtp-password"
    # Optional overrides (defaults are set for your Gmail relay)
    # mail_username = "luca@sparagino.it"
    # mail_host     = "smtp-relay.gmail.com"
    ```

### How to generate the `app_key`
Run the following command in your terminal to generate a production-ready key without affecting your local environment:
```bash
php artisan key:generate --show
```
Copy the entire output (e.g., `base64:un6U...`) and paste it into your `terraform.tfvars`.
3.  Apply:
    ```bash
    terraform init
    terraform apply
    ```

## Step 2: Build & Push Containers
We now have TWO containers to push.

### Backend API
```bash
gcloud auth configure-docker asia-southeast1-docker.pkg.dev
docker build -t asia-southeast1-docker.pkg.dev/[PROJECT_ID]/korp-repo/api:latest .
docker push asia-southeast1-docker.pkg.dev/[PROJECT_ID]/korp-repo/api:latest
```

### Frontend Client
```bash
docker build -f frontend.Dockerfile -t asia-southeast1-docker.pkg.dev/[PROJECT_ID]/korp-repo/client:latest .
docker push asia-southeast1-docker.pkg.dev/[PROJECT_ID]/korp-repo/client:latest
```

## Step 3: DNS Configuration (Manual on AWS Route53)
After running Terraform, Google Cloud Run will provide DNS records for domain verification and mapping.

1.  Go to the GCP Console -> **Cloud Run** -> **Manage Custom Domains**.
2.  Google will likely ask you to verify ownership of `sparagino.it`. Follow the TXT record instructions.
3.  Once verified, Google will give you **CNAME** or **A/AAAA** records for:
    - `api-korp.sparagino.it` -> Maps to the API service.
    - `client-korp.sparagino.it` -> Maps to the Client service.
4.  Apply these records in your **AWS Route53** hosted zone for `sparagino.it`.

## Step 4: Database Migration

To run the migrations over the private VPC network, use the **Private IP address** of your Cloud SQL instance. You can find this in the Terraform output after applying, or in the GCP Console under SQL -> `korp-db`.

```bash
# Deploy a temporary job for migrations
gcloud run jobs create migrate \
  --image asia-southeast1-docker.pkg.dev/[PROJECT_ID]/korp-repo/api:latest \
  --command "php,artisan,migrate,--force" \
  --region asia-southeast1 \
  --vpc-connector korp-connector \
  --set-env-vars "DB_HOST=[DB_PRIVATE_IP],DB_DATABASE=korp,DB_USERNAME=korp_user,DB_PASSWORD=[YOUR_DB_PASSWORD]"

gcloud run jobs execute migrate --region asia-southeast1
```

## Step 5: Environment Variables (.env)

In this serverless setup, you **do not** upload a `.env` file to the server. Environment variables are managed in two ways:

1.  **Sensitive Data (Secret Manager)**:
    - `APP_KEY` and `DB_PASSWORD` are stored in Google Secret Manager by Terraform.
    - They are automatically injected into the container at runtime.

2.  **Application Config (Terraform/Cloud Run)**:
    - Common variables like `APP_ENV=production`, `APP_DEBUG=false`, and database connection details are defined in `terraform/cloud_run.tf`.
    - If you need to add more environment variables (e.g., `MAIL_HOST`, `STRIPE_KEY`):
        - Open `terraform/cloud_run.tf`.
        - Add a new `env` block inside the `containers` section of the `backend` service:
          ```hcl
          env {
            name  = "MY_VAR"
            value = "my-value"
          }
          ```
        - Run `terraform apply` to redeploy the service with the new variables.

## Crucial Note on Frontend API URL
Before building the frontend (`npm run build`), ensure your API URL is correctly configured. In a production build, you typically set this in `client/.env.production`:
```env
VITE_API_BASE_URL=https://api-korp.sparagino.it/api/v0
```

## Accessing the App
- **Frontend**: `https://client-korp.sparagino.it`
- **Backend API**: `https://api-korp.sparagino.it`
