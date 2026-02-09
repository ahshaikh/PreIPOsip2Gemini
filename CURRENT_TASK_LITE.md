# CURRENT_TASK_LITE.md — PreIPOsip

## Task
What exactly is broken or being changed?

---

## Actor & Scope
- Actor: (Admin / Company Admin / Company User / System)
- Company scope: (single / cross — cross is invalid unless stated)

---

## Safety Check (Answer YES/NO)
- Backend remains authoritative?
- Tenant isolation preserved?
- No UI-only enforcement?
- Null-safe (no assumed config/data)?

If any answer is NO → STOP.

---

## Expected Outcome
What must work after this fix?

---

## Done Means
- Error no longer reproducible
- No new silent failures introduced
- No instruction ends with “try refresh / re-login”

---
END
