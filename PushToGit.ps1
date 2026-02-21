# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "refactor(test-architecture): remove RefreshDatabase globally and stabilize chargeback accounting semantics

TEST INFRASTRUCTURE
- Removed RefreshDatabase from all test classes (~110 files)
- Standardized on DatabaseTransactions via base TestCase
- Eliminated per-class migrate:fresh overhead (~80–100s per class)
- Enforced single schema bootstrap strategy
- Preserved test assertions (no weakening)

SCHEMA FIXES
- Added migration: add_reversal_fields_to_user_investments_table
  - reversed_at (nullable timestamp)
  - reversal_reason (nullable string)
- Fixed factory mismatch (plans.status → is_active)

CHARGEBACK INTEGRATION HARDENING
- Removed duplicate manual ledger entries in ChargebackIntegrationTest
  (WalletService already creates INVESTMENT ledger entries)
- Fixed double-entry inflation of SHARE_SALE_INCOME

FINANCIAL LOGIC CORRECTIONS
- Centralized wallet mutation for chargebacks via WalletService
- Debits full chargeback amount deterministically
- Captures wallet shortfall and records Accounts Receivable
- Ensures:
  - No negative wallet balance
  - Idempotent confirmation
  - Ledger symmetry preserved
  - Accounting equation balances

MODEL ADJUSTMENTS
- Updated UserInvestment reversal handling to avoid unintended wallet credit during chargebacks
- Ensured chargeback reversals do not create phantom wallet refunds

LEDGER CONSISTENCY
- Ensured correct account flows for:
  - Bank clawback
  - Wallet liability
  - Share sale income reversal
  - Accounts receivable (shortfall tracking)

RESULT
- All ChargebackIntegrationTest passing
- All ChargebackInvariantsTest passing
- Test runtime significantly improved after removal of RefreshDatabase
- Financial drift invariants preserved

This commit stabilizes chargeback lifecycle semantics, removes systemic test overhead, and restores deterministic accounting behavior."
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