Write-Host ""
Write-Host "============================================"
Write-Host " FINANCIAL MUTATION BOUNDARY AUDIT"
Write-Host "============================================"
Write-Host ""

$ROOT = "backend/app"

Write-Host "============================================"
Write-Host "---- Wallet Mutations ----"
Write-Host "============================================"
rg -n "walletService->deposit|walletService->withdraw|depositLegacy|withdrawLegacy" $ROOT

Write-Host ""
Write-Host "============================================"
Write-Host "---- Ledger Mutations ----"
Write-Host "============================================"
rg -n "LedgerService|AdminLedger|recordCampaignDiscount|recordLedger" $ROOT

Write-Host ""
Write-Host "============================================"
Write-Host "---- Database Transactions ----"
Write-Host "============================================"
rg -n "DB::transaction" $ROOT

Write-Host ""
Write-Host "============================================"
Write-Host "---- Pessimistic Locks ----"
Write-Host "============================================"
rg -n "lockForUpdate" $ROOT

Write-Host ""
Write-Host "============================================"
Write-Host "---- Direct Wallet Model Mutations ----"
Write-Host "============================================"
rg -n "increment\(|decrement\(|balance\s*=" $ROOT

Write-Host ""
Write-Host "============================================"
Write-Host "Audit complete."
Write-Host "============================================"