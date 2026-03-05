Purpose
This document defines exactly which files participate in financial mutation during a payment lifecycle.
Anything outside this scope must NOT mutate money.

Financial Lifecycle Scope:

Entry Points
app/Services/PaymentWebhookService.php
app/Services/Orchestration/FinancialOrchestrator.php

Financial Domain Services
app/Services/WalletService.php
app/Services/AllocationService.php
app/Services/BonusCalculatorService.php

Orchestration Layer
app/Services/Orchestration/FinancialOrchestrator.php
app/Services/Orchestration/Operations/*

Async Jobs (to be restricted)
app/Jobs/ProcessSuccessfulPaymentJob.php
app/Jobs/ProcessPaymentBonusJob.php

Idempotency
app/Services/IdempotencyService.php

Financial Models
app/Models/Payment.php
app/Models/Subscription.php
app/Models/Wallet.php
app/Models/Product.php
app/Models/UserInvestment.php
app/Models/BonusTransaction.php

Money Representation
app/ValueObjects/Money.php

Lifecycle Invariants
The payment lifecycle must guarantee:
1️. Single DB transaction

2️. Strict lock order
Payment
→ Subscription
→ Wallet
→ Product
→ UserInvestment
→ BonusTransaction

3️. Wallet Passbook Sequence
+ Principal Deposit
- Allocation Withdrawal
+ Bonus Credit

4️. Allocation Invariant
amount_paise = allocated_paise + remainder_paise

5️. Idempotency
Repeated webhook → no duplicate financial mutation