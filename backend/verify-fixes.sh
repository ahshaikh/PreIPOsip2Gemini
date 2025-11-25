#!/bin/bash

echo "🔍 Verifying all critical fixes..."

# 1. Check duplicate migration is gone
echo "1. Checking for duplicate migrations..."
if [ -f "database/migrations/2025_11_24_093145_create_canned_responses_table.php" ]; then
    echo "❌ FAIL: Duplicate migration still exists"
    exit 1
else
    echo "✅ PASS: Duplicate migration removed"
fi

# 2. Check session encryption
echo "2. Checking session encryption..."
if grep -q "'encrypt' => true" backend/config/session.php; then
    echo "✅ PASS: Session encryption enabled"
else
    echo "❌ FAIL: Session encryption not enabled"
    exit 1
fi

# 3. Check SameSite
echo "3. Checking SameSite cookie..."
if grep -q "'same_site' => 'strict'" backend/config/session.php; then
    echo "✅ PASS: SameSite set to strict"
else
    echo "❌ FAIL: SameSite not strict"
    exit 1
fi

# 4. Check SQL injection fix
echo "4. Checking SQL injection fix..."
if grep -q "whereRaw.*INTERVAL sla_hours" backend/app/Services/SupportService.php; then
    echo "❌ FAIL: SQL injection still present"
    exit 1
else
    echo "✅ PASS: SQL injection fixed"
fi

# 5. Check rate limiting
echo "5. Checking rate limiting..."
if grep -q "RateLimiter::for('financial'" backend/app/Providers/RouteServiceProvider.php; then
    echo "✅ PASS: Rate limiting configured"
else
    echo "❌ FAIL: Rate limiting not configured"
    exit 1
fi

# Run tests
echo "6. Running tests..."
php artisan test
if [ $? -eq 0 ]; then
    echo "✅ PASS: All tests passing"
else
    echo "❌ FAIL: Some tests failing"
    exit 1
fi

echo ""
echo "🎉 ALL CRITICAL FIXES VERIFIED!"
echo "You're ready to move to medium-priority items."