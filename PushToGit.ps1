# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "fix(recovery): resolve AutoDebit loop, payment 500, transaction drift + enforce integration correctness

CRITICAL FIXES
- AutoDebitService: prevent infinite retry loop when razorpay_subscription_id is null
  - Validate subscription ID before payment creation
  - Stop recursive RetryAutoDebitJob dispatch
  - Ensure deterministic suspension after retry threshold
- PaymentRequestTest: mock PaymentGatewayInterface to prevent real API calls (fix 500 error)
- TransactionTest:
  - Use existing wallet created by UserFactory (avoid duplicate wallet creation)
  - Align TransactionFactory enum from 'bonus' to 'bonus_credit'
  - Restore balance_before / balance_after integrity

BILLING & DOMAIN ALIGNMENT
- Confirm upgrade billing doctrine: flat differential (newAmount - oldAmount), not time-based proration
- Rename tests to reflect differential charge terminology
- Ensure subscription amount explicitly matches plan for determinism

SEEDER & TDS CORRECTIONS
- Make ProductSeeder self-contained (remove UserSeeder coupling)
- Fix CelebrationBonusService TDS type ('celebration_bonus' → 'celebration')
- Validate celebration TDS config and thresholds

INTEGRATION HARDENING
- Restore strict encryption test (assertNotEquals + assertEquals exact match)
- Remove Cache::flush() and verify natural invalidation
- Confirm allowed_mimes override is per-call and secure
- Ensure webhook signature verification remains intact

All targeted recovery test suites passing without memory override.
No middleware bypass.
No weakened assertions.
No silent financial semantic changes."
#----------------------

function Get-GitCredential {
    param (
        [string]$Message
    )
    Write-Host $Message
    Write-Host "NOTE: Paste your token. It will be hidden (no characters will appear)." -ForegroundColor Yellow
    $credential = $host.ui.ReadLineAsSecureString()
    return [System.Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($credential)
    )
}

# --- Script ---
Write-Host "Starting Git deployment for PreIPOsip.com..." -ForegroundColor Green

# 1. Check if Git is installed
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: Git is not installed or not found in your PATH." -ForegroundColor Red
    Write-Host "Please install Git for Windows and try again."
    pause
    exit
}

# 2. Check if already a Git repository
if (Test-Path ".git") {
    Write-Host "Git repository already initialized."
} else {
    Write-Host "Initializing new Git repository..."
    git init
    if ($?) { Write-Host "Git init successful." -ForegroundColor Green }
}

# 3. Add all files
Write-Host "Adding all project files to staging..."
git add .
Write-Host "Files staged." -ForegroundColor Green

# 4. Create initial commit
Write-Host "Creating initial commit..."
git commit -m $CommitMessage
Write-Host "Commit created: '$CommitMessage'" -ForegroundColor Green

# 5. Set default branch to 'main'
git branch -M main
Write-Host "Default branch set to 'main'."

# 6. Check for remote
$remote = git remote
if ($remote -like "*origin*") {
    Write-Host "Remote 'origin' already exists."
} else {
    Write-Host "Connecting to remote: $GithubRepoURL"
    git remote add origin $GithubRepoURL
    if ($?) { Write-Host "Remote added successfully." -ForegroundColor Green }
}

# 7. Get Credentials & Push
Write-Host "Preparing to push to $GithubRepoURL"
Write-Host "You will be prompted for your GitHub Personal Access Token (PAT)."

try {
    $Token = Get-GitCredential -Message "Enter your GitHub PAT:"

    # We must re-build the URL to include the token for the push
    # Format: https://<TOKEN>@github.com/user/repo.git
    $PaddedURL = $GithubRepoURL.Insert(8, "$Token@")

    Write-Host "Pushing to 'origin main'..."
    git push -u $PaddedURL main

    if ($?) {
        Write-Host "SUCCESS: Project has been pushed to GitHub." -ForegroundColor Green
    } else {
        Write-Host "ERROR: Push failed. Check your token and repository URL." -ForegroundColor Red
    }
} catch {
    Write-Host "An error occurred during push: $_" -ForegroundColor Red
}

Write-Host "Script finished. Press Enter to exit."
pause