# Storage Setup Instructions

This document explains the setup required for payment proof uploads and storage access.

## Issue Fixed
Payment proof files were returning **403 Forbidden** error when accessed via the admin panel.

## Root Causes
1. Missing frontend environment variable configuration
2. Missing Laravel storage symbolic link
3. Missing storage directories

## Setup Steps

### 1. Frontend Environment Configuration

Create `frontend/.env.local` file (already done):
```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1/
```

**Important:** After creating/modifying `.env.local`, you must **restart the Next.js development server** for changes to take effect.

```bash
cd frontend
# Stop current dev server (Ctrl+C)
npm run dev
```

### 2. Laravel Storage Symbolic Link

Laravel requires a symbolic link from `public/storage` to `storage/app/public` for web-accessible files.

**Already created manually:**
```bash
cd backend/public
ln -s ../storage/app/public storage
```

**Alternative (if composer is installed):**
```bash
cd backend
php artisan storage:link
```

### 3. Storage Directories

Created with proper permissions (already done):
```bash
mkdir -p backend/storage/app/public/payment_proofs
chmod -R 775 backend/storage/app/public
```

## How It Works

### Upload Flow:
1. User submits manual payment with screenshot/PDF proof
2. File stored in: `backend/storage/app/public/payment_proofs/{user_id}/{filename}`
3. Database saves path: `payment_proofs/{user_id}/{filename}`

### Access Flow:
1. Admin clicks Eye icon in payments table
2. Frontend calls `getStorageUrl('payment_proofs/37/1766691258_694d91ba50f1f.pdf')`
3. Helper constructs: `http://localhost:8000/storage/payment_proofs/37/1766691258_694d91ba50f1f.pdf`
4. Laravel serves file via symlink: `public/storage → storage/app/public`

## Verification

### Check Symlink Exists:
```bash
ls -la backend/public/storage
# Should show: storage -> ../storage/app/public
```

### Check Directory Permissions:
```bash
ls -la backend/storage/app/public/
# Should show drwxrwxr-x for payment_proofs
```

### Test Upload:
1. User dashboard → Subscribe Plan → Choose Plan → Upload payment proof
2. Admin → Payments → Find transaction → Click Eye icon
3. File should open in new tab at: `http://localhost:8000/storage/payment_proofs/...`

## Troubleshooting

### 403 Forbidden
- Check symlink exists: `ls -la backend/public/storage`
- Check file permissions: `chmod -R 775 backend/storage/app/public`
- Check file exists: `ls -la backend/storage/app/public/payment_proofs/`

### 404 Not Found
- Restart Next.js dev server after creating `.env.local`
- Clear browser cache
- Check `NEXT_PUBLIC_API_URL` in browser console: `console.log(process.env.NEXT_PUBLIC_API_URL)`

### URL shows "undefined"
- Environment variable not set at build time
- Restart Next.js dev server
- Verify `.env.local` file exists in `frontend/` directory

## Production Setup

For production deployment:

1. Set `NEXT_PUBLIC_API_URL` in hosting provider environment variables
2. Run `php artisan storage:link` on production server
3. Ensure `storage/app/public` has write permissions (775 or 755)
4. Configure web server (Nginx/Apache) to serve Laravel's public directory
5. Test file uploads immediately after deployment

## Security Notes

- `.env.local` is in `.gitignore` (never commit it)
- Files in `storage/app/public/` are publicly accessible via web
- Consider adding authentication middleware for sensitive file access
- Validate file types before storage (already implemented in PaymentController)
- Use signed URLs for temporary file access in production
