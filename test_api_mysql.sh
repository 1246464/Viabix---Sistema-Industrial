#!/bin/bash

# Test api_mysql.php endpoint
echo "=== Testing api_mysql.php ==="

# Get CSRF token from check_session
echo "1. Getting CSRF token..."
RESPONSE=$(curl -s -c /tmp/cookies.txt https://viabix.com.br/api/check_session.php)
echo "Response: $RESPONSE"
TOKEN=$(echo $RESPONSE | sed -n 's/.*"csrf_token":"\([^"]*\)".*/\1/p')
echo "Token extracted: '$TOKEN'"

if [ -z "$TOKEN" ]; then
    echo "ERROR: Failed to extract CSRF token!"
    exit 1
fi

# Perform login
echo ""
echo "2. Logging in..."
LOGIN_RESPONSE=$(curl -s -b /tmp/cookies.txt -c /tmp/cookies.txt -X POST https://viabix.com.br/api/login.php \
  -H 'Content-Type: application/json' \
  --data-raw "{\"login\":\"admin\",\"password\":\"123456\",\"_csrf_token\":\"$TOKEN\"}")
echo "Login Response: $LOGIN_RESPONSE"

# Check if login was successful
if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
    echo "Login successful!"
else
    echo "Login failed - checking error..."
    echo "Response: $LOGIN_RESPONSE"
fi

# Verify session
echo ""
echo "3. Verifying session..."
SESSION=$(curl -s -b /tmp/cookies.txt https://viabix.com.br/api/check_session.php)
echo "Session: $SESSION"

# Test api_mysql
echo ""
echo "4. Testing api_mysql.php with testConnection..."
API_RESPONSE=$(curl -s -b /tmp/cookies.txt -X POST https://viabix.com.br/Controle_de_projetos/api_mysql.php -d "action=testConnection")
echo "API Response: $API_RESPONSE"

# Test getProjects
echo ""
echo "5. Testing api_mysql.php with getProjects..."
PROJECTS=$(curl -s -b /tmp/cookies.txt -X POST https://viabix.com.br/Controle_de_projetos/api_mysql.php -d "action=getProjects")
echo "Projects Response: $PROJECTS"
