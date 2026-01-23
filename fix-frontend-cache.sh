#!/bin/bash

echo "🔧 Fixing Next.js Frontend Cache Issues..."
echo ""

cd /home/user/PreIPOsip2Gemini/frontend

echo "1️⃣  Stopping Next.js dev server..."
pkill -f "next dev" 2>/dev/null || echo "   No running dev server found"

echo ""
echo "2️⃣  Clearing .next build cache..."
rm -rf .next
echo "   ✓ Cache cleared"

echo ""
echo "3️⃣  Clearing node_modules/.cache..."
rm -rf node_modules/.cache
echo "   ✓ Module cache cleared"

echo ""
echo "✅ Cache cleared successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. cd /home/user/PreIPOsip2Gemini/frontend"
echo "   2. npm run dev"
echo "   3. Open browser and hard refresh (Ctrl+Shift+R)"
echo ""
