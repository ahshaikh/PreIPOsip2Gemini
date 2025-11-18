# ==============================================================
# Start-3.ps1 - Single-Window Job Runner with Logging
# ==============================================================

$backendPath = "C:\preiposip\backend"
$frontendPath = "C:\preiposip\frontend"
$logRoot = "C:\preiposip\logs"

if (!(Test-Path $logRoot)) {
    New-Item -ItemType Directory -Path $logRoot | Out-Null
}

Write-Host "== PreIPOsip Single-Window Job Runner (Start-3) ==" -ForegroundColor Cyan

# Ensure background jobs inherit PATH correctly
$machinePath = [Environment]::GetEnvironmentVariable("Path", "Machine")
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")
$env:Path = "$machinePath;$userPath"

function Start-NamedJob {
    param(
        [string]$JobName,
        [scriptblock]$Script
    )
    $job = Start-Job -Name $JobName -ScriptBlock $Script
    Write-Host "Started: $JobName (Job ID: $($job.Id))" -ForegroundColor Yellow
}

# ---------------- BACKEND JOB --------------------------------
$backendSB = {
    param($path, $logRoot)

    Set-Location $path
    $logFile = Join-Path $logRoot "backend.log"

    "Backend job started" | Out-File $logFile -Encoding utf8

    if (!(Test-Path ".env")) {
        "Copying .env" | Out-File $logFile -Append
        Copy-Item ".env.example" ".env"
    }

    try {
        php artisan key:generate | Out-File $logFile -Append
    } catch {
        "APP_KEY generation failed" | Out-File $logFile -Append
    }

    composer install --no-interaction | Out-File $logFile -Append
    php artisan migrate --seed --force | Out-File $logFile -Append
    php artisan serve --port=8000 | Out-File $logFile -Append
}

# ---------------- QUEUE JOB ----------------------------------
$queueSB = {
    param($path, $logRoot)

    Set-Location $path
    $logFile = Join-Path $logRoot "queue.log"

    "Queue job started" | Out-File $logFile -Encoding utf8
    php artisan queue:work --sleep=3 --tries=3 | Out-File $logFile -Append
}

# ---------------- FRONTEND JOB -------------------------------
$frontendSB = {
    param($path, $logRoot)

    Set-Location $path
    $logFile = Join-Path $logRoot "frontend.log"

    "Frontend job started" | Out-File $logFile -Encoding utf8
    npm install --no-audit --silent | Out-File $logFile -Append
    npm run dev | Out-File $logFile -Append
}

# Start all jobs
Start-NamedJob -JobName "preiposip-backend" -Script { & $backendSB $using:backendPath $using:logRoot }
Start-Sleep -Seconds 1

Start-NamedJob -JobName "preiposip-queue" -Script { & $queueSB $using:backendPath $using:logRoot }
Start-Sleep -Seconds 1

Start-NamedJob -JobName "preiposip-frontend" -Script { & $frontendSB $using:frontendPath $using:logRoot }

Write-Host "All jobs started." -ForegroundColor Green
Write-Host "Logs saved at: $logRoot" -ForegroundColor Cyan
Write-Host "To view logs:  notepad $logRoot\backend.log" -ForegroundColor Yellow
Write-Host "To view job output:  Get-Job | Receive-Job -Keep" -ForegroundColor Yellow
Write-Host "To stop all: Get-Job | Stop-Job; Get-Job | Remove-Job" -ForegroundColor Red

Start-Sleep -Seconds 2
Start-Process "http://localhost:3000"
