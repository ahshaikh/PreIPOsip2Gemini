#!/bin/bash

echo "========================================="
echo "Complete Cache Clearing Script"
echo "========================================="
echo ""

# Frontend
echo "1. Clearing Frontend Caches..."
cd /home/user/PreIPOsip2Gemini/frontend

# Kill any running processes
echo "   - Stopping any running Next.js processes..."
pkill -f "next dev" 2>/dev/null || true
sleep 2

# Clear all caches
echo "   - Removing .next directory..."
rm -rf .next

echo "   - Removing node_modules/.cache..."
rm -rf node_modules/.cache

echo "   - Removing .turbo..."
rm -rf .turbo

echo "   - Clearing npm cache..."
npm cache clean --force 2>/dev/null || true

echo "✓ Frontend caches cleared"
echo ""

# Backend
echo "2. Clearing Backend Caches..."
cd /home/user/PreIPOsip2Gemini/backend

# Kill any running Laravel processes
echo "   - Stopping any running Laravel processes..."
pkill -f "artisan serve" 2>/dev/null || true
sleep 2

# Clear Laravel caches (if vendor exists)
if [ -d "vendor" ]; then
    echo "   - Clearing Laravel caches..."
    php artisan config:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    echo "✓ Laravel caches cleared"
else
    echo "⚠ Laravel vendor not found (run composer install first)"
fi
echo ""

echo "========================================="
echo "All Caches Cleared!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Backend: cd backend && php artisan serve"
echo "2. Frontend: cd frontend && npm run dev"
echo "3. Browser: Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)"
echo ""
echo "Then test login at: http://localhost:3000/login"
echo ""
