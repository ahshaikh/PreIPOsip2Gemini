# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "feat(epic-4): close all remaining commercial compliance gaps with atomic ledger linkage

EPIC 4 — Lock Commercial Boundaries (FINAL CLOSURE)

This commit closes all outstanding EPIC 4 compliance gaps and finalizes
non-bypassable commercial invariants across inventory, allocation, and ledger flows.

GAP 1 — Inventory Creation Without Financial Atomicity (CRITICAL)
- Linked BulkPurchase creation to platform ledger debit atomically
- Prevented inventory materialization without confirmed financial provenance
- Eliminated orphan inventory scenarios

GAP 2 — Unknown User Allocation Concurrency Safety (CRITICAL)
- Enforced allocation safety via transactional locking
- Prevented race conditions allowing over-allocation or double-spend
- Ensured wallet and inventory state remain consistent under concurrency

GAP 3 — Inventory Invariant Lives in Controller (HIGH)
- Moved all commercial invariants to model hooks / domain services
- Removed reliance on controller-level enforcement
- Ensured invariants apply uniformly across API, jobs, and CLI

GAP 4 — STORY 4.3: Platform Ledger Linkage (MANDATORY)
- Established authoritative ledger linkage for all inventory creation
- Ledger logic remains additive (no refactor of AllocationService)
- Financial provenance is now mandatory for all commercial actions

ARCHITECTURAL BOUNDARY (INTENTIONAL)
- Commercial compliance is enforced at model + domain layer only
- No UI, controller, or job may bypass these guards
- Violations fail hard with explicit exceptions
- No silent fallbacks or deferred failures permitted

This commit freezes EPIC 4 and completes the commercial compliance perimeter."
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