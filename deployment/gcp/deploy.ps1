# deploy.ps1
param (
    [Parameter(Mandatory=$true)]
    [string]$ProjectId,

    [string]$Region = "asia-southeast1",
    [string]$RepoName = "korp-repo"
)

$ErrorActionPreference = "Stop"

Write-Host "--- Starting Deployment to GCP ($Region) ---" -ForegroundColor Cyan

# 1. Authenticate Docker
Write-Host "[1/4] Authenticating Docker..." -ForegroundColor Yellow
gcloud auth configure-docker "${Region}-docker.pkg.dev" --quiet

# 2. Build Backend API
Write-Host "[2/4] Building Backend API..." -ForegroundColor Yellow
$apiImage = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/api:latest"
docker build -t $apiImage .

# 3. Build Frontend Client
Write-Host "[3/4] Building Frontend Client..." -ForegroundColor Yellow
$clientImage = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/client:latest"
docker build -f frontend.Dockerfile -t $clientImage .

# 4. Push to Artifact Registry
Write-Host "[4/4] Pushing images to Artifact Registry..." -ForegroundColor Yellow
docker push $apiImage
docker push $clientImage

Write-Host "`n--- Success! ---" -ForegroundColor Green
Write-Host "Images are now in Artifact Registry."
Write-Host "If you updated environment variables in Terraform, run 'terraform apply' now."
Write-Host "Otherwise, Cloud Run will pick up the 'latest' images on the next service update or deployment."
