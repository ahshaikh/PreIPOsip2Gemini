# ---------------------------
# PreIPOsip One-Click Launcher
# ---------------------------

$backendPath = "C:\preiposip\backend"
$frontendPath = "C:\preiposip\frontend"

Write-Output "Starting PreIPOsip environment..."

# ---------------------------
# Function to run PowerShell in a new window
# ---------------------------
function Start-NewWindow {
    param([string]$Command)
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $Command -Verb RunAs
}

# ---------------------------
# Backend window
# ---------------------------
$backendCommand = @"
cd $backendPath

if (!(Test-Path ".env")) {
    Write-Output "Copying .env..."
    copy .env.example .env
}

if ((Get-Content .env | Select-String "APP_KEY=" -SimpleMatch).Line -eq "APP_KEY=") {
    Write-Output "Generating app key..."
    php artisan key:generate
}

Write-Output "Running composer..."
composer install

Write-Output "Running migrations..."
php artisan migrate --seed

Write-Output "Starting Laravel server..."
php artisan serve --port=8000
"@

Start-NewWindow $backendCommand

# ---------------------------
# Queue worker window
# ---------------------------
$queueCommand = @"
cd $backendPath
php artisan queue:work
"@

Start-NewWindow $queueCommand

# ---------------------------
# Frontend window
# ---------------------------
$frontendCommand = @"
cd $frontendPath
npm install
npm run dev
"@

Start-NewWindow $frontendCommand

# ---------------------------
# Open browser
# ---------------------------
Start-Process "http://localhost:3000"

Write-Output "All systems starting. PreIPOsip is launching..."
