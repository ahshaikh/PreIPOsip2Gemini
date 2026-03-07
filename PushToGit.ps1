# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "feat(architecture): introduce Single Financial Orchestration Boundary blueprint and stabilization audit

Summary

* Completed deep architectural audit of financial lifecycle flows.
* Identified multiple competing lifecycle engines causing nondeterministic behavior.
* Established final blueprint for a Single Financial Orchestration Boundary.

Key Findings

* Multiple lifecycle engines detected:

  * PaymentWebhookService lifecycle
  * FinancialOrchestrator lifecycle
  * executePaymentAllocationSaga() flow
  * Legacy lifecycle jobs
* Overlapping paths can trigger duplicate wallet credits, allocations, or bonuses.
* Saga infrastructure exists but is mostly dormant and contributes to architecture drift.

Architecture Decision

* FinancialOrchestrator becomes the single financial mutation boundary.
* PaymentWebhookService will be reduced to a thin gateway adapter.
* Legacy lifecycle jobs will be converted into orchestrator adapters.
* Saga-based lifecycle (executePaymentAllocationSaga) will be deprecated and removed.

Design Principles Finalized

* Integer-only monetary system using paise (BigInt).
* Money Value Object for all financial operations.
* Strict lock ordering: Product → User → Wallet → Subscription.
* Single DB transaction per payment lifecycle.
* Deterministic unit-of-work model for all financial mutations.

Migration Plan Defined
Phase 0 – Architecture guardrails and invariant tests
Phase 1 – Money Value Object + float elimination
Phase 2 – Single transaction boundary in FinancialOrchestrator
Phase 3 – O(1) allocation refactor for inventory updates
Phase 4 – Lifecycle consolidation and saga removal
Phase 5 – Refund / chargeback negative saga
Phase 6 – Side-effect purge (post-commit jobs)

Safety Controls

* Planned CI guardrails:

  * Float elimination test
  * Single transaction boundary test
  * Rogue wallet mutation test
  * Ledger invariant verification

Notes

* No functional changes applied yet; this commit documents the finalized migration blueprint and audit conclusions to guide phased implementation."
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