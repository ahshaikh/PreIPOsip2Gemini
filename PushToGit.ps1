# push-to-github.ps1
#
# This script initializes a Git repository, connects to a GitHub remote,
# and pushes the entire project (backend, frontend, docs) to the main branch.
#
# RUN IN: PowerShell (as Admin or with execution policy set)
# USAGE: .\PushToGit.ps1

# --- Configuration ---
$GithubRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini"
$CommitMessage = "feat(compliance): enforce immutable company disclosure tiers and public visibility boundary

EPIC 3 / STORY 3.1 — Restore Disclosure Authority

This commit establishes a hard compliance boundary governing company disclosure
tiers and public visibility.

KEY INVARIANTS (NON-NEGOTIABLE):
- Every company has exactly one disclosure_tier
- disclosure_tier is IMMUTABLE except via explicit promotion authority
- Public visibility is FORBIDDEN unless disclosure_tier >= tier_2_live
- Products inherit visibility strictly from their company

ARCHITECTURAL ENFORCEMENT:
- Introduced DisclosureTier enum as single source of truth
- Added CompanyDisclosureTierService as the SOLE authority for tier promotion
  (monotonic, no downgrade, no skipping)
- Blocked all direct mutation paths (fill, update, save) at model level
- Added explicit DisclosureTierImmutabilityException for violations
- Enforced visibility at query layer via global scopes:
  - Company: PublicVisibilityScope
  - Product: ProductPublicVisibilityScope
- Global scopes prevent accidental public exposure by default

SAFETY & RELIABILITY:
- Promotion uses raw DB updates to intentionally bypass model guards
- Immutability violations are logged safely in HTTP, CLI, and job contexts
- No schema, migration, or seeder changes

TEST COVERAGE:
- Promotion authority enforcement
- Direct mutation rejection
- Public visibility exclusion guarantees
- Company ↔ Product visibility invariant consistency

This commit freezes the disclosure authority boundary.
Any future change to visibility or disclosure rules MUST build on this layer."
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