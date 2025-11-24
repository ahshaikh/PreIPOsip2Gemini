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

Write-Output "Clearing the enviornment..."
php artisan optimize:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear

Write-Output "Starting Laravel server..."
# php artisan serve --port=8000
php artisan serve
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
