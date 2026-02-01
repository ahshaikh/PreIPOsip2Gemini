# PreIPOsip Database Audit Line

Effective Date: **1 February 2026**

From this date onward, all database schema changes, migrations, and financial
data structures in PreIPOsip MUST enforce regulator-grade guarantees at the
database level.

This includes (but is not limited to):
- Deterministic migrations
- Non-polymorphic ownership
- Integer-based monetary representation
- Enforced referential integrity
- Immutable financial history
- Mandatory audit timestamps

All migrations prior to this audit line are considered **legacy**.
They are frozen and will not be retroactively modified.

This audit line establishes a clear compliance boundary between
legacy schema and hardened schema going forward.
