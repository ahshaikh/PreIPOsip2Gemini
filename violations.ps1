Write-Host ""
Write-Host "======================================"
Write-Host " FINANCIAL MUTATION BOUNDARY (STRICT)"
Write-Host "======================================"

$ROOT = "backend/app"

$pattern = "walletService->deposit|walletService->withdraw|depositLegacy|withdrawLegacy|AdminLedger|LedgerService|DB::transaction|lockForUpdate"

$results = rg -n $pattern $ROOT

foreach ($line in $results) {

    if (
        $line -notmatch "FinancialOrchestrator" -and
        $line -notmatch "WalletService" -and
        $line -notmatch "LedgerService"
    ) {
        Write-Host $line -ForegroundColor Red
    }

}

Write-Host ""
Write-Host "======================================"
Write-Host "Violations above must be moved to FinancialOrchestrator"
Write-Host "======================================"