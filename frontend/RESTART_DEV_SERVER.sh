#!/bin/bash

# Script to properly restart Next.js dev server after cache clear
# Run this from the frontend directory

echo "🧹 Cleaning all caches..."
rm -rf .next
rm -rf .turbo
rm -rf node_modules/.cache
rm -rf .swc

echo "🔪 Killing any running Node processes..."
pkill -f "next dev" || true
pkill -f "node.*next" || true

echo "✅ Verifying environment configuration..."
if [ ! -f ".env.local" ]; then
    echo "❌ ERROR: .env.local not found!"
    echo "Creating .env.local..."
    cat > .env.local << 'EOF'
# API Configuration
NEXT_PUBLIC_API_URL=http://localhost:8000

# Environment
NODE_ENV=development
EOF
    echo "✅ Created .env.local"
else
    echo "✅ .env.local exists"
    cat .env.local
fi

echo ""
echo "🔍 Verifying profile page has correct code..."
if grep -q "const BACKEND_URL = process.env.NEXT_PUBLIC_API_URL?.replace" app/company/profile/page.tsx; then
    echo "✅ BACKEND_URL constant found"
else
    echo "❌ ERROR: BACKEND_URL constant not found!"
    exit 1
fi

if grep -q 'src={`${BACKEND_URL}/storage/${company.logo}`}' app/company/profile/page.tsx; then
    echo "✅ Image URL uses BACKEND_URL correctly"
else
    echo "❌ ERROR: Image URL does not use BACKEND_URL!"
    exit 1
fi

echo ""
echo "🎯 All checks passed! Ready to start dev server."
echo ""
echo "To start the dev server, run:"
echo "  npm run dev"
echo ""
echo "Or run this script with 'start' argument:"
echo "  ./RESTART_DEV_SERVER.sh start"

if [ "$1" = "start" ]; then
    echo ""
    echo "🚀 Starting dev server..."
    npm run dev
fi
