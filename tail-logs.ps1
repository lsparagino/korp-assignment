## Laravel Log Tailer for Windows
## Usage: .\tail-logs.ps1 [-Lines 50] [-Filter "error"]

param(
    [int]$Lines = 0,
    [string]$Filter = ""
)

$logFile = Join-Path $PSScriptRoot "storage\logs\laravel.log"

if (-not (Test-Path $logFile)) {
    Write-Host "Log file not found: $logFile" -ForegroundColor Red
    exit 1
}

Write-Host "Tailing $logFile (last $Lines lines)..." -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop." -ForegroundColor DarkGray
Write-Host ""

if ($Filter) {
    Get-Content $logFile -Tail $Lines -Wait | Where-Object { $_ -match $Filter }
}
else {
    Get-Content $logFile -Tail $Lines -Wait
}
