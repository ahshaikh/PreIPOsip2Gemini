# ==========================================
# PreIPOsip Start-2 (Smart Launcher)
# ==========================================

$backendPath = "C:\preiposip\backend"
$frontendPath = "C:\preiposip\frontend"
$mysqlService = "mysql"

Write-Host "== PreIPOsip Smart Launcher (Start-2) ==" -ForegroundColor Cyan

# ------------------------------------------------------------
# Function: Open PowerShell admin window with correct quoting
# ------------------------------------------------------------
function Launch-AdminWindow {
    param([string]$Title, [string]$Command)

    $full = "-NoExit -Command `"Write-Host '$Title' -ForegroundColor Cyan; $Command`""

    Start-Process powershell -Verb RunAs -ArgumentList $full
}

# ------------------------------------------------------------
# Check MySQL service
# ------------------------------------------------------------
try {
    $svc = Get-Service -Name $mysqlService -ErrorAction Stop
    if ($svc.Status -ne "Running") {
        Write-Host "Starting MySQL service..." -ForegroundColor Yellow
        Start-Service $mysqlService
    }
} catch {
    Write-Warning "MySQL service '$mysqlService' not found."
}

# ------------------------------------------------------------
# Backend startup command (fully escaped)
# ------------------------------------------------------------
$backendCmd = @"
cd "$backendPath"

# .env check
if (!(Test-Path .env)) {
    Write-Host 'Copying .env'
    Copy-Item .env.example .env
}

# Ensure APP_KEY exists
try {
    if (-not (Select-String -Path .env -Pattern '^APP_KEY=')) {
        Write-Host 'Generating APP_KEY'
        php artisan key:generate
    }
} catch {
    Write-Host 'Generating APP_KEY (fallback)'
    php artisan key:generate
}

Write-Host 'Composer install'
composer install --no-interaction

Write-Host 'Running migrations'
php artisan migrate --seed --force

Write-Host 'Starting backend'
php artisan serve --port=8000
"@

# ------------------------------------------------------------
# Queue worker command
# ------------------------------------------------------------
$queueCmd = @"
cd "$backendPath"
php artisan queue:work --sleep=3 --tries=3
"@

# ------------------------------------------------------------
# Frontend command
# ------------------------------------------------------------
$frontendCmd = @"
cd "$frontendPath"
npm install --no-audit --silent
npm run dev
"@

# ------------------------------------------------------------
# Launch all three windows
# ------------------------------------------------------------
Launch-AdminWindow -Title "PreIPOsip Backend (Start-2)" -Command $backendCmd
Start-Sleep -Milliseconds 500

Launch-AdminWindow -Title "PreIPOsip Queue (Start-2)" -Command $queueCmd
Start-Sleep -Milliseconds 500

Launch-AdminWindow -Title "PreIPOsip Frontend (Start-2)" -Command $frontendCmd

# ------------------------------------------------------------
# Attempt to open browser after a delay
# ------------------------------------------------------------
Start-Sleep -Seconds 4
Start-Process "http://localhost:3000"

Write-Host "Start-2 launched successfully." -ForegroundColor Green
