Write-Host "`n=== Running Pint (Laravel Linter) ===" -ForegroundColor Cyan
./vendor/bin/pint
if ($LASTEXITCODE -ne 0) { Write-Host "Pint failed!" -ForegroundColor Red; exit 1 }
Write-Host "Pint passed!" -ForegroundColor Green

Write-Host "`n=== Running ESLint (Client Linter) ===" -ForegroundColor Cyan
Set-Location client
npm run lint
$exitCode = $LASTEXITCODE
Set-Location ..
if ($exitCode -ne 0) { Write-Host "ESLint failed!" -ForegroundColor Red; exit 1 }
Write-Host "ESLint passed!" -ForegroundColor Green

Write-Host "`n=== Running Vitest (Client Unit Tests) ===" -ForegroundColor Cyan
Set-Location client
npm run test
$exitCode = $LASTEXITCODE
Set-Location ..
if ($exitCode -ne 0) { Write-Host "Vitest failed!" -ForegroundColor Red; exit 1 }
Write-Host "Vitest passed!" -ForegroundColor Green

Write-Host "`n=== Running Pest (PHP Tests) ===" -ForegroundColor Cyan
php artisan test
if ($LASTEXITCODE -ne 0) { Write-Host "Pest failed!" -ForegroundColor Red; exit 1 }
Write-Host "Pest passed!" -ForegroundColor Green

Write-Host "`n=== Running Playwright (E2E Tests) ===" -ForegroundColor Cyan
Set-Location client
npm run e2e
$exitCode = $LASTEXITCODE
Set-Location ..
if ($exitCode -ne 0) { Write-Host "Playwright failed!" -ForegroundColor Red; exit 1 }
Write-Host "Playwright passed!" -ForegroundColor Green

Write-Host "`n=== All checks passed! ===" -ForegroundColor Green
