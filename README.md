# SecureWallet

A multi-company financial wallet management system built with Laravel 12 and Vue.js 3 (Vuetify).

## 🚀 Setup Instructions

### Backend (Laravel)

1.  **Clone the repository** and navigate to the project root.
2.  **Install PHP dependencies**:
    ```bash
    composer install
    ```
3.  **Environment Setup**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    - *Note*: Ensure `APP_URL` in `.env` matches your local server (e.g., `http://localhost:8000`).
4.  **Database Setup**:
    - Create a database if not using SQLite (SQLite is the default in `.env.example`).
    - Run migrations and seed data:
      ```bash
      php artisan migrate --seed
      ```
5.  **Serve the application**:
    - **Option A (Artisan)**: Run `php artisan serve`.
    - **Option B (Herd/Valet)**: Ensure the folder is linked or parked (accessible at `http://securewallet.test`).
    - Test the health check at `{APP_URL}/up`.

### Frontend (Vue.js)

1.  Navigate to the `client` directory:
    ```bash
    cd client
    ```
2.  **Install dependencies**:
    ```bash
    npm install
    ```
3.  **Environment Setup**:
    - Create/Edit `client/.env`.
    - Set `VITE_API_BASE_URL` to match your backend URL (e.g., `http://localhost:8000/api/v0` or `http://securewallet.test/api/v0`).
4.  **Start development server**:
    ```bash
    npm run dev
    ```

---

## 🔐 Credentials

A default admin account is created during seeding:

- **Email**: `admin@example.com`
- **Password**: `password`
  
Additionally, a member account with no assigned wallets is created during seeding:

- **Email**: `member@example.com`
- **Password**: `password`


---

## 📊 Database Seeding

To reset and seed the database with fresh dummy data (Wallets, Transactions, and an Acme Corp company):

```bash
php artisan migrate:fresh --seed
```

### Dummy Data Summary
- **Companies**: Acme Corp
- **Users**: Admin User, Member User
- **Wallets**: Multiple USD and EUR wallets, ready to be assigned.
- **Transactions**: 50+ mixed internal and external transactions.

---

## ⚠️ Known Issues

- Newly registered users cannot interact with the application because they don't have a company. The only way to access the platform for new users is to be invited to a team.
- Changing email should trigger a new email validation process.
- The email verification process is currently not implemented.
- Fortify implementation is very basic.
- Email layouts are not customized.
- There's no localization.
- The API base path `/api/v0` is currently redundant, since server and client are each deployed on a dedicated third level domain. In this scenario, `/v0` alone could have been enough.

---

## 💡 Assumptions and Interpretations

- Instead of applying an "initial balance" to the wallet (I assumed that a newly created wallet should have zero balance), I introduced **external transactions**. If all transactions are between internal wallets starting at zero balance, the overall balance will always be 0. External transactions prevent this by adding or removing money from the user's wallets.
- It's unclear how users become admins. In this basic implementation, admin roles can only be set from the database. Regarding members, there are currently two ways to create them:
    - A user registers using the sign-up form: in this scenario, the user has no company and must be invited to a team. The address is immediately added since the user is already verified (email verification not yet implemented).
    - An admin invites an email address that is not already registered. In this scenario, I implemented an **invitation mechanism**, so that the user can accept the invite and create a password for their account.
- I assumed wallets with at least one transaction cannot be deleted. Only newly created wallets can be deleted.
- Delete operations require the user to type a "pin" shown on screen. This is mostly to showcase a delete protection for critical operations.
- The infrastructure has been deployed on **GCP (Singapore region)** with Terraform, using 2 separate Cloud Run services and a very small MySQL instance to keep costs to a minimum. Due to low performance settings and cold starts, you might experience delays.
- This is currently a **mono repo** for convenience. Depending on company policies or personal preference, it could be split into client and server repos.


---

## 🛠 API Endpoints Summary

All API routes are prefixed with `/api/v0/`. All protected routes require a `Bearer` token.

### 🔐 Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/login` | Authenticate and receive token. |
| `POST` | `/register` | Create a new account. |
| `POST` | `/logout` | Revoke current session (Protected). |
| `POST` | `/two-factor-challenge` | Verify TOTP or recovery codes. |
| `POST` | `/forgot-password` | Request password reset link. |
| `POST` | `/reset-password` | Update password with reset token. |
| `GET` | `/invitation/{token}` | Verify invitation token. |
| `POST` | `/accept-invitation/{token}` | Accept invitation and set password. |

### 👤 Profile & User (Protected)
| Method | Endpoint | Description                             |
| :--- | :--- |:----------------------------------------|
| `GET` | `/user` | Get current authenticated user details. |
| `POST` | `/user/confirm-password` | Confirm password.                       |
| `PATCH` | `/settings/profile` | Update profile information.             |
| `DELETE` | `/settings/profile` | Delete user account.                    |
| `PUT` | `/settings/password` | Update account password.                |
| `POST` | `/email/verification-notification` | Resend verification email.              |
| `GET` | `/email/verify/{id}/{hash}` | Verify email address.                   |

### 🛡️ Two-Factor Authentication (Protected)
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/user/two-factor-qr-code` | Get 2FA setup QR code. |
| `POST` | `/user/two-factor-authentication` | Enable 2FA. |
| `POST` | `/user/confirmed-two-factor-authentication` | Confirm 2FA setup. |
| `DELETE` | `/user/two-factor-authentication` | Disable 2FA. |
| `GET` | `/user/two-factor-recovery-codes` | List recovery codes. |
| `POST` | `/user/two-factor-recovery-codes` | Regenerate recovery codes. |

### 💰 Financials & Management (Protected)
| Method | Endpoint | Description                           |
| :--- | :--- |:--------------------------------------|
| `GET` | `/dashboard` | Financial overview (balances, top wallets, recent transactions). |
| `GET` | `/companies` | List user's companies.                |
| `GET` | `/transactions` | List all transactions (filterable by type, date, amount, reference, wallet). |
| `GET/POST` | `/wallets` | List or create wallets.               |
| `GET/PUT/DELETE` | `/wallets/{wallet}` | Manage a specific wallet.             |
| `PATCH` | `/wallets/{wallet}/toggle-freeze` | Freeze/Unfreeze a wallet.             |
| `GET/POST` | `/team-members` | List or invite team members.          |
| `PUT/DELETE` | `/team-members/{member}` | Manage team members.                  |

---

## ☁️ Deployment (GCP Cloud Run)

The application deploys to Google Cloud Platform using Terraform. Infrastructure is defined in `terraform/`.

### Quick Deploy

```powershell
.\deployment\gcp\deploy.ps1 -ProjectId "your-project-id" -Apply
```

This builds and pushes Docker images, then runs `terraform apply` to update Cloud Run services.

### Database Migration

Create and run a Cloud Run job to execute migrations against the remote database:

```bash
# Create the migration job (first time only)
gcloud run jobs create migrate \
  --image {region}-docker.pkg.dev/{your-project-id}/korp-repo/api:latest \
  --command "php,artisan,migrate:fresh,--seed,--force" \
  --region {region} \
  --vpc-connector korp-connector \
  --set-env-vars "DB_CONNECTION=mysql,DB_HOST={db_ip},DB_DATABASE={db_name},DB_USERNAME={db_user},DB_PASSWORD='{db_pass}'"

# Execute the migration
gcloud run jobs execute migrate --region {region}
```

### Cost Optimization
Both Cloud Run services are configured for minimal cost:
- **CPU throttling** enabled (billed only during request processing).
- **Scale-to-zero** (minScale = 0) — no charges when idle.
- **Max instances** capped at 2 per service.
- **Database** uses the smallest Cloud SQL tier (`db-f1-micro`).

> **Note**: First request after idle may have a cold start delay (~2-5s).
