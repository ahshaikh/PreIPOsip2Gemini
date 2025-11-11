# V-DEPLOY-1730-001
<#
.SYNOPSIS
    Automates adding, committing, and pushing all project changes
    to the 'ahshaikh/PreIPOsip2Gemini' repository.
.DESCRIPTION
    This script intelligently handles the Git workflow:
    1.  Checks if Git is installed.
    2.  Initializes a Git repository if it doesn't exist.
    3.  Verifies the 'origin' remote URL. If it's wrong, it updates it. If it doesn't exist, it adds it.
    4.  Stages all new, modified, and deleted files.
    5.  Commits the changes using the message you provide.
    6.  Pushes the commit to the 'main' branch.
.PARAMETER CommitMessage
    (Required) The commit message for this batch of changes.
.EXAMPLE
    # Run this from your PowerShell terminal in the project root
    .\push-to-github.ps1 -CommitMessage "feat: Initial commit of all 200+ files"
.EXAMPLE
    # A shorter, positional version
    .\push-to-github.ps1 "fix: Updated login controller logic"
.NOTES
    # To use this: Open PowerShell in your project's root folder.
    # If this is your first time, you may need to allow scripts: 
    # Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
Run the script with your message: .\push-to-github.ps1 "feat: This is my commit message"
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
Write-Host "Starting Git push automation for PreIPOsip..." -ForegroundColor Cyan

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

# Check and configure remote 'origin'
$currentRemote = git remote -v | Where-Object { $_ -like "$RemoteName`t*" }

if ($currentRemote -and ($currentRemote -notlike "*$RemoteRepoURL*")) {
    Write-Host "Updating incorrect remote '$RemoteName' URL..." -ForegroundColor Yellow
    git remote set-url $RemoteName $RemoteRepoURL
} elseif (-not $currentRemote) {
    Write-Host "Adding new remote '$RemoteName'..." -ForegroundColor Yellow
    git remote add $RemoteName $RemoteRepoURL
} else {
    Write-Host "Remote '$RemoteName' is
    correctly configured." -ForegroundColor Green
}

Write-Host "Staging all files..."
git add .

Write-Host "Committing changes with message: '$CommitMessage'"
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
    Write-Error "Push failed. Please check your credentials (you may need a Personal Access Token), network connection, and repository permissions."
}