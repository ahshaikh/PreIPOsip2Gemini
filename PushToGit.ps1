# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "feat(audit-hardening): stabilize migrations, enforce inventory invariants, close company module audit gaps

SUMMARY
--------
Comprehensive architectural stabilization and audit remediation across
Company, Inventory, Allocation, Snapshot, and Eligibility layers.

This commit resolves migration instability, removes dual allocation contracts,
hardens financial invariants, and implements missing audit tooling identified
in the Company Module Audit Report.

MIGRATION & SCHEMA STABILIZATION
---------------------------------
- Removed non-deterministic Schema::hasTable() guards
- Fixed migration ordering to ensure parent tables created before dependents
- Eliminated FK drop inconsistencies affecting migrate:fresh
- Standardized paise-only monetary schema alignment
- Ensured rollback safety and deterministic fresh rebuild

INVENTORY & ALLOCATION HARDENING
---------------------------------
- AllocationService now consistently throws InsufficientInventoryException
- Removed legacy return-false inventory failure paths
- Refactored allocateSharesLegacy() to use typed exceptions
- Eliminated phantom Product stub in global shortage handling
- InsufficientInventoryException now supports nullable Product (clean contract)
- Added explicit isGlobalShortage() indicator for audit clarity

ELIGIBILITY & TOCTOU PROTECTION
--------------------------------
- Added commit-time buy eligibility re-verification
- Wrapped enforcement behind config flag for test isolation
- Introduced config/eligibility.php
- Added ENFORCE_ELIGIBILITY_AT_COMMIT toggle for .env.testing
- Ensured transaction rollback safety (no partial mutation risk)

AUDIT GAP REMEDIATION
---------------------
- Implemented InventoryReconciliationJob
- Implemented SnapshotIntegrityAuditJob
- Added verifySnapshotIntegrity() and hash documentation (SHA-256)
- Fixed platform snapshot time-gap vulnerability
- Added InventoryTraceabilityReportService (audit-ready JSON output)
- Dispatched CompanyTierChanged event post-transaction commit
- Strengthened disclosure-tier governance flow

FACTORY STABILIZATION
---------------------
- ProductFactory default set to inactive (no implicit inventory side effects)
- Added explicit activeWithInventory() and legacyActive() states
- Added WithdrawalFactory state coverage (approved, processed, rejected)
- Adjusted TransactionFactory defaults for deterministic test behavior

ROUTE & MIDDLEWARE VALIDATION
-----------------------------
- Verified audit and inventory routes under sanctum + admin role guards
- Ensured no route guard leakage into web middleware

ARCHITECTURAL IMPACT
--------------------
- No weakening of financial invariants
- No modification to DoubleEntryLedgerService core logic
- No alteration of FIFO allocation logic
- No mutation of wallet service invariants
- Deterministic migrate:fresh restored
- Inventory conservation contract strengthened

This commit transitions the Company Module from Production-Ready
to Audit-Hardened while preserving financial correctness and
ledger symmetry guarantees."
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