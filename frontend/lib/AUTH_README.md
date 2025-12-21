# Role helper (frontend/lib/auth.ts)

This helper centralizes role detection logic used across the frontend to avoid
inconsistent redirects caused by varying API response shapes and role naming.

Functions:
- normalizeRoleString(value): normalizes strings like `ROLE_ADMIN`, `super-admin`, `super_admin` -> `admin` / `superadmin`.
- extractRoleNames(userData): inspects `roles[]`, `role`, `role_name`, `is_admin`, and nested shapes to produce a list of normalized role names.

Usage:
import { extractRoleNames } from '@/lib/auth';

const roles = extractRoleNames(response.data.user || response.data);

Running tests:
From the project root (frontend), run your JS test runner. If using npm:

cd frontend
npm install
npm test

(Adjust command if the repo uses vitest/jest scripts — the new test is compatible with common runners.)

---