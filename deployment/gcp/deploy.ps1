# deploy.ps1
param (
    [Parameter(Mandatory = $true)]
    [string]$ProjectId,

    [string]$Region = "asia-southeast1",
    [string]$RepoName = "korp-repo",
    [switch]$Apply
)

$ErrorActionPreference = "Stop"

# Generate a unique tag based on timestamp
$Tag = Get-Date -Format "yyyyMMdd-HHmmss"

Write-Host "--- Starting Deployment to GCP ($Region) ---" -ForegroundColor Cyan
Write-Host "Tag: $Tag" -ForegroundColor Magenta

# 1. Authenticate Docker
Write-Host "[1/4] Authenticating Docker..." -ForegroundColor Yellow
gcloud auth configure-docker "${Region}-docker.pkg.dev" --quiet

# 2. Build Backend API
Write-Host "[2/4] Building Backend API..." -ForegroundColor Yellow
$apiImageLatest = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/api:latest"
$apiImageVersion = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/api:$Tag"
docker build -t $apiImageLatest -t $apiImageVersion .

# 3. Build Frontend Client
Write-Host "[3/4] Building Frontend Client..." -ForegroundColor Yellow
$clientImageLatest = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/client:latest"
$clientImageVersion = "${Region}-docker.pkg.dev/${ProjectId}/${RepoName}/client:$Tag"
docker build -f frontend.Dockerfile -t $clientImageLatest -t $clientImageVersion .

# 4. Push to Artifact Registry
Write-Host "[4/4] Pushing images to Artifact Registry..." -ForegroundColor Yellow
docker push $apiImageLatest
docker push $apiImageVersion
docker push $clientImageLatest
docker push $clientImageVersion

Write-Host "`n--- Success! ---" -ForegroundColor Green
Write-Host "Images are now in Artifact Registry."

$terraformCmd = "terraform apply -var=""api_image_tag=$Tag"" -var=""client_image_tag=$Tag"""

if ($Apply) {
    Write-Host "`n--- Running Terraform Apply ---" -ForegroundColor Cyan
    $currentDir = Get-Location
    try {
        Set-Location "terraform"
        Write-Host "Executing: $terraformCmd" -ForegroundColor Yellow
        Invoke-Expression "$terraformCmd -auto-approve"
    }
    finally {
        Set-Location $currentDir
    }
}
else {
    Write-Host "Run the following command to update Cloud Run:" -ForegroundColor Yellow
    Write-Host $terraformCmd -ForegroundColor Green
}
