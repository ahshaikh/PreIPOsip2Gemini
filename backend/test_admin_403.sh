#!/bin/bash
# Admin 403 Error Diagnostic Script
# Run this to identify why admin routes are returning 403

echo "=========================================="
echo "ADMIN 403 ERROR DIAGNOSTIC"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Check if backend is running${NC}"
if curl -s http://localhost:8000/up > /dev/null; then
    echo -e "${GREEN}✓ Backend is running${NC}"
else
    echo -e "${RED}✗ Backend is not running on port 8000${NC}"
    echo "  Start with: cd backend && php artisan serve"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Test admin login to get a valid token${NC}"
echo "Enter admin email:"
read admin_email

echo "Enter admin password:"
read -s admin_password
echo ""

echo "Testing login..."
login_response=$(curl -s -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$admin_email\",\"password\":\"$admin_password\"}")

token=$(echo $login_response | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$token" ]; then
    echo -e "${RED}✗ Login failed${NC}"
    echo "Response: $login_response"
    exit 1
else
    echo -e "${GREEN}✓ Login successful${NC}"
    echo "Token: ${token:0:20}..."
fi

echo ""
echo -e "${YELLOW}Step 3: Test admin dashboard with token${NC}"
dashboard_response=$(curl -s -X GET http://localhost:8000/api/v1/admin/dashboard \
  -H "Authorization: Bearer $token" \
  -w "\n%{http_code}")

http_code=$(echo "$dashboard_response" | tail -n1)
response_body=$(echo "$dashboard_response" | head -n-1)

echo "HTTP Status: $http_code"
echo "Response: $response_body"

if [ "$http_code" = "200" ]; then
    echo -e "${GREEN}✓ Admin dashboard accessible${NC}"
    exit 0
elif [ "$http_code" = "403" ]; then
    echo -e "${RED}✗ 403 Forbidden - Checking possible causes...${NC}"
    echo ""
    echo "Possible causes:"
    echo "1. IP Whitelist: Your IP might not be in the whitelist"
    echo "2. Missing Role: User might not have 'admin' or 'super-admin' role"
    echo "3. Wrong User Type: Token might belong to CompanyUser, not User"
    echo ""
    echo -e "${YELLOW}Check Laravel logs:${NC}"
    echo "  tail -f backend/storage/logs/laravel.log"
    echo ""
    echo "Look for these log entries:"
    echo "  [ADMIN-IP-CHECK] - Shows IP whitelist status"
    echo "  [ADMIN-AUTH] - Shows authentication flow"
elif [ "$http_code" = "401" ]; then
    echo -e "${RED}✗ 401 Unauthorized - Authentication failed${NC}"
    echo "Token might be invalid or expired"
else
    echo -e "${RED}✗ Unexpected status code: $http_code${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Check Laravel logs for details${NC}"
echo "Run: tail -30 backend/storage/logs/laravel.log | grep -A 5 -B 5 'ADMIN'"
