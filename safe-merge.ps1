# Save as safe-merge-claude.ps1 and run from C:\preipo
# PowerShell: .\safe-merge-claude.ps1

$ErrorActionPreference = 'Stop'

function Fail($msg) {
  Write-Host "ERROR: $msg" -ForegroundColor Red
  exit 1
}

# 1) Ensure repo root
if (-not (Test-Path ".git")) { Fail "Run this script from the repository root (C:\preipo)." }

# 2) Ensure working tree is clean
$porcelain = git status --porcelain
if ($porcelain) {
  Fail "Working tree is not clean. Commit or stash changes before running this script.`ngit status --porcelain output:`n$porcelain"
}

# 3) Fetch all remotes
Write-Host "Fetching all remotes..."
git fetch --all --prune

# 4) Ensure main up-to-date and checkout main
Write-Host "Checking out main and updating from origin/main..."
git checkout main
git pull origin main --ff-only

# 5) Create a timestamped temp branch from main
$ts = (Get-Date).ToString("yyyyMMddHHmmss")
$temp = "claude-update-$ts"
Write-Host "Creating temporary branch: $temp"
git checkout -b $temp

# 6) Merge Claude's remote branch into temp branch
$claudeRef = "origin/claude/analyze-repo-01FrFxLN51LZ7G1dwTrWJkPp"
Write-Host "Merging $claudeRef into $temp..."
# Attempt a merge; if conflicts arise, abort and fail
try {
  git pull origin claude/analyze-repo-01FrFxLN51LZ7G1dwTrWJkPp
} catch {
  Write-Host "`nMerge produced conflicts or failed. Resolve in branch $temp or abort. Aborting script." -ForegroundColor Yellow
  # leave the repo in merge state for manual resolution
  exit 1
}

# 7) Detect any tracked junk that must be removed from index
$junkPaths = @(
  "frontend/node_modules",
  "frontend/.next",
  "frontend/dist",
  "backend/vendor"
)

$needCleanup = $false
$toRemove = @()

foreach ($p in $junkPaths) {
  # check if any tracked file matches the pattern
  try {
    $tracked = git ls-files -- "$p/*" 2>$null
  } catch {
    $tracked = @()
  }
  if ($tracked -and $tracked.Count -gt 0) {
    $needCleanup = $true
    $toRemove += $p
    Write-Host "Detected tracked files under: $p"
  }
}

if ($needCleanup) {
  Write-Host "`nCleaning tracked junk (will remove from git index, not from disk)..."
  foreach ($r in $toRemove) {
    # use --ignore-unmatch so it doesn't error if nothing matched
    git rm -r --cached --ignore-unmatch $r
  }
  git add .
  git commit -m "Clean Claude update: remove tracked build/vendor/node_modules files"
  Write-Host "Cleanup committed."
} else {
  Write-Host "No tracked node_modules/.next/dist/vendor files detected in merge. No cleanup commit required."
}

# 8) Final sanity check: ensure temp branch has no unresolved conflicts
$porcelain2 = git status --porcelain
if ($porcelain2 -match '^UU|^AA|^DD|^AU|^UD|^UA') {
  Fail "Unresolved merge conflicts remain in $temp. Resolve them manually before continuing."
}

# 9) Merge into main (no fast-forward to preserve merge record)
Write-Host "Switching to main and merging $temp..."
git checkout main

# If temp is already merged, this will say "Already up to date."
$mergeOutput = git merge --no-ff $temp 2>&1
Write-Host $mergeOutput

# If merge failed, abort
if ($LASTEXITCODE -ne 0) {
  Fail "Merge into main failed. Resolve manually in branch $temp."
}

# 10) Push main to origin
Write-Host "Pushing main -> origin/main..."
git push origin main

# 11) Delete temporary branch locally
Write-Host "Deleting temporary branch $temp..."
git branch -d $temp

Write-Host "`nSUCCESS: Claude's branch merged and main pushed. Repo is clean." -ForegroundColor Green
