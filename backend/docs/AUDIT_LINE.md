# PreIPOsip Database Audit Line

Effective Date: **1 February 2026**

From this date onward, all database schema changes, migrations, and financial data structures in PreIPOsip MUST enforce regulator-grade guarantees at the database level.

This includes (but is not limited to):
- Deterministic migrations
- Non-polymorphic ownership
- Integer-based monetary representation
- Enforced referential integrity
- Immutable financial history
- Mandatory audit timestamps

All migrations prior to this audit line are considered **legacy**.
They are frozen and will not be retroactively modified.

This audit line establishes a clear compliance boundary between legacy schema and hardened schema going forward.

This was Step 1 - Decision making.

Step 2 — Freeze legacy tables (READ-ONLY by policy)
For all pre–audit-line data:
- No schema rewrites
- No “fix-up” migrations
- No retroactive constraints

Instead:
- Mark them as legacy
- Disallow writes except via controlled reconciliation jobs
- Treat them as historical records, not active truth

We do not repair fossils. We label them.

Step 3 — Create a V2 Financial Core (this is the most important move)
Do not mutate:
- transactions
- wallets
- investments
- allocations

Create new tables instead:
- ledger_entries_v2
- wallet_balances_v2
- investment_positions_v2
- allocations_v2

Hard rules for V2:
- No polymorphism
- Integer paise only
- Enum-backed state machines
- Mandatory timestamps
- Immutable records
- FK everywhere, no exceptions

This is how major fintech's have evolved.

Step 4 — Enforce invariants only in V2 (ruthlessly)
Examples:
- status = 'paid' ⇒ paid_at IS NOT NULL
- Ledger entries are append-only
- Ownership is non-null and non-polymorphic
- No deletes, only reversals

This gives us:
- Provable correctness
- Clean audits
- Mathematical consistency

Step 5 — Bridge legacy → V2 with explicit provenance
When old data feeds new logic:
- Copy forward
- Never “fix in place”
- Always record:
  - source_table
  - source_id
  - migration_version
  - confidence_level

This makes auditors relax.

Why this is the best possible move (not just “a good one”)
Alternative			Why it fails
Fix everything retroactively	Impossible to prove correctness
Ignore the audit		Guaranteed future failure
Patch migrations		Increases non-determinism
Rewrite history			Regulator nightmare

This approach gives us:
- Technical safety
- Regulatory credibility
- Development velocity
- A clean future

The one sentence we should internalize
“We don’t claim the past was perfect — we prove the future is correct.”
