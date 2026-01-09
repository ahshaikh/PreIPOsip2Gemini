# Next.js Turbopack Cache Issue - Resolution Guide

**Issue**: Seeing errors about `undefined/storage/...` even after code fixes
**Cause**: Next.js 16.0.1 Turbopack is serving stale cached build
**Status**: ✅ Code is correct, just needs cache clear + dev server restart

---

## Verification Completed ✅

### 1. Source Code Status
- ✅ **profile/page.tsx**: Uses `BACKEND_URL` correctly (line 17, 309)
- ✅ **team/page.tsx**: Uses `BACKEND_URL` correctly (line 19, 119, 315)
- ✅ **No old code**: Only 1 occurrence of `process.env.NEXT_PUBLIC_API_URL` (correct usage in constant definition)

### 2. Environment Configuration
- ✅ `.env.local` exists with: `NEXT_PUBLIC_API_URL=http://localhost:8000`
- ✅ Git working tree clean (all changes committed)

### 3. Cache Cleanup Performed
- ✅ Removed `.next` directory (main build cache)
- ✅ Removed `.turbo` directory (Turbopack cache)
- ✅ Removed `node_modules/.cache` (module cache)
- ✅ Killed all running Node processes

---

## The Problem

The error trace you're seeing shows **line numbers from the OLD code**:

```javascript
// ERROR SHOWS THIS (OLD CODE):
> 298 |  <Image src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${company.logo}`}

// BUT THE ACTUAL FILE HAS THIS (NEW CODE):
309 |  src={`${BACKEND_URL}/storage/${company.logo}`}
```

**Why?** Next.js Turbopack cached the old transpiled JavaScript. The error stack trace references the cached build, not your source files.

---

## Solution: Restart Dev Server

### Option 1: Use the Helper Script (Recommended)
```bash
cd /home/user/PreIPOsip2Gemini/frontend
./RESTART_DEV_SERVER.sh start
```

### Option 2: Manual Steps
```bash
cd /home/user/PreIPOsip2Gemini/frontend

# 1. Kill dev server (Ctrl+C or:)
pkill -f "next dev"

# 2. Clear all caches
rm -rf .next .turbo node_modules/.cache .swc

# 3. Start fresh
npm run dev
```

---

## How to Verify Fix is Working

Once dev server restarts, you should see:

1. **No console errors** about "Invalid URL" or "undefined/storage/..."
2. **Images load correctly** or show proper error fallback (AlertCircle icon)
3. **Console log shows**: `http://localhost:8000/storage/company-logos/...` (not `undefined/storage/...`)

---

## What Was Fixed (Already Done)

### ✅ Frontend Changes Committed
- Created `.env.local` with proper configuration
- Fixed `profile/page.tsx` (309: uses `BACKEND_URL`)
- Fixed `team/page.tsx` (315: uses `BACKEND_URL`)
- Added comprehensive validation
- Added error handling with fallback UI

### ✅ Git Status
```bash
Commit: 3bf82dc
Message: "fix: Resolve image URL construction errors in company portal + comprehensive validation"
Branch: claude/audit-preipopsip-platform-MNnCa
Status: Pushed to origin
```

---

## Why This Happened

**Next.js 16.0.1 with Turbopack** has aggressive caching. When you made changes:

1. Source files were updated ✅
2. Git commit was made ✅
3. **BUT** the running dev server continued serving the old cached build ❌

The dev server needs to be **fully restarted** (not just hot-reloaded) after:
- Environment variable changes (`.env.local` creation)
- Major structural changes (new constants, imports)
- Cache corruption

---

## Prevention for Future

After making changes that involve:
- Environment variables
- New constants at module level
- Major refactoring

Always restart the dev server completely:
```bash
# Stop dev server (Ctrl+C)
# Clear cache
rm -rf .next .turbo
# Start fresh
npm run dev
```

---

## Technical Details

### File Structure Verified

**frontend/app/company/profile/page.tsx (529 lines)**
```typescript
// Line 17: Constant definition with fallback
const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL?.replace('/api/v1', '') || 'http://localhost:8000';

// Line 309: Image src uses BACKEND_URL
src={`${BACKEND_URL}/storage/${company.logo}`}

// Line 315: Error logging uses BACKEND_URL
console.error('Failed to load logo:', `${BACKEND_URL}/storage/${company.logo}`);
```

**frontend/app/company/team/page.tsx (395 lines)**
```typescript
// Line 19: Same constant definition
const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL?.replace('/api/v1', '') || 'http://localhost:8000';

// Line 119: Preview uses BACKEND_URL
setPhotoPreview(`${BACKEND_URL}/storage/${member.photo_path}`);

// Line 315: Image src uses BACKEND_URL
src={`${BACKEND_URL}/storage/${member.photo_path}`}
```

**frontend/.env.local**
```env
NEXT_PUBLIC_API_URL=http://localhost:8000
NODE_ENV=development
```

### How BACKEND_URL Works

1. **Build Time**: Next.js replaces `process.env.NEXT_PUBLIC_API_URL` with value from `.env.local`
2. **Runtime**: If env var is missing, falls back to `'http://localhost:8000'`
3. **`.replace('/api/v1', '')`**: Strips API suffix since storage URLs need base server URL only

**Result**:
- With .env.local: `http://localhost:8000/storage/...` ✅
- Without .env.local: `http://localhost:8000/storage/...` ✅ (fallback)
- Never: `undefined/storage/...` ❌ (this was the old code)

---

## Summary

**Current State**:
- ✅ All code fixes applied and committed
- ✅ Environment configuration correct
- ✅ All caches cleared
- ⏳ **Action Required**: Restart dev server

**Expected Outcome**:
- ✅ No "undefined/storage" errors
- ✅ Images load from `http://localhost:8000/storage/...`
- ✅ Error fallback shows AlertCircle icon
- ✅ All validation works correctly

---

**Last Updated**: 2026-01-09
**Created By**: Claude (fixing Turbopack cache issue)
