<#
.SYNOPSIS
    Automates adding, committing, and pushing all changes
    in the current directory to the 'ahshaikh/PreIPOsip.git' repository.
.DESCRIPTION
    This script performs the following actions:
    1. Checks if the current directory is a Git repository.
    2. Initializes Git if it's not already.
    3. Checks if the 'origin' remote exists and points to the correct URL.
    4. Adds all new and modified files to staging.
    5. Commits the changes with a user-provided message.
    6. Pushes the commit to the 'main' branch of 'origin'.
.PARAMETER CommitMessage
    (Required) The commit message for the changes.
.EXAMPLE
    .\push-to-github.ps1 -CommitMessage "feat: Add user dashboard components"
.EXAMPLE
    .\push-to-github.ps1 "fix: Corrected login API validation"
#>
[CmdletBinding()]
param (
    [Parameter(Mandatory=$true, Position=0)]
    [string]$CommitMessage
)

# --- 1. Configuration ---
$RemoteName = "origin"
$BranchName = "main"
$RemoteRepoURL = "https://github.com/ahshaikh/PreIPOsip2Gemini.git"

# --- 2. Script Execution ---
Write-Host "Starting Git push automation..." -ForegroundColor Cyan

# Check for Git
if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
    Write-Error "Git is not installed or not found in your PATH. Please install Git and try again."
    return
}

# Check if it's a Git repository
if (-not (Test-Path -Path ".git")) {
    Write-Host "Not a Git repository. Initializing..." -ForegroundColor Yellow
    git init
}

# Check and configure remote
$remote = git remote -v | Where-Object { $_ -like "$RemoteName`t$RemoteRepoURL*" }
if ($remote) {
    Write-Host "Remote '$RemoteName' is correctly configured." -ForegroundColor Green
} else {
    # Check if a different 'origin' exists
    if (git remote -v | Where-Object { $_ -like "$RemoteName`t*" }) {
        Write-Host "Updating remote '$RemoteName' URL..." -ForegroundColor Yellow
        git remote set-url $RemoteName $RemoteRepoURL
    } else {
        Write-Host "Adding new remote '$RemoteName'..." -ForegroundColor Yellow
        git remote add $RemoteName $RemoteRepoURL
    }
}

Write-Host "Staging all files..."
git add .

Write-Host "Committing changes..."
git commit -m $CommitMessage

# Check if commit was successful (e.g., if there were no changes)
if ($LASTEXITCODE -ne 0) {
    Write-Warning "Commit failed. This may be because there are no changes to commit."
    return
}

Write-Host "Pushing to $RemoteName $BranchName..." -ForegroundColor Cyan
git push $RemoteName $BranchName

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Successfully pushed to $RemoteRepoURL" -ForegroundColor Green
} else {
    Write-Error "Push failed. Please check your credentials and network connection."
}

