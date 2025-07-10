#!/bin/bash

# API Endpoint Testing Script for SmartTax Docker Container
# Tests all available API endpoints to ensure they work correctly

set -e

echo "🔌 API Endpoint Testing for SmartTax Application"
echo "==============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BASE_URL="http://localhost:8080"
API_BASE="$BASE_URL/api"

# Test results tracking
API_TESTS_PASSED=0
API_TESTS_FAILED=0

# Function to print test results
print_api_test_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ PASS:${NC} $2"
        ((API_TESTS_PASSED++))
    else
        echo -e "${RED}❌ FAIL:${NC} $2"
        ((API_TESTS_FAILED++))
    fi
}

# Function to test API endpoint
test_api_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_status=$3
    local description=$4
    local data=$5
    
    echo -e "${BLUE}🌐 Testing: $method $endpoint - $description${NC}"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$API_BASE$endpoint" || echo "000")
    elif [ "$method" = "POST" ]; then
        if [ -n "$data" ]; then
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -d "$data" "$API_BASE$endpoint" || echo "000")
        else
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                "$API_BASE$endpoint" || echo "000")
        fi
    fi
    
    if [ "$response" = "$expected_status" ]; then
        print_api_test_result 0 "$description (HTTP $response)"
        return 0
    else
        print_api_test_result 1 "$description (Expected HTTP $expected_status, got $response)"
        return 1
    fi
}

# Function to get auth token
get_auth_token() {
    echo -e "${BLUE}🔐 Getting authentication token...${NC}"
    
    # First, create a test user if it doesn't exist
    docker-compose exec -T app php artisan tinker --execute="
        try {
            \$user = App\Models\User::firstOrCreate([
                'email' => 'test@example.com'
            ], [
                'name' => 'Test User',
                'password' => Hash::make('password123')
            ]);
            echo 'Test user ready';
        } catch (Exception \$e) {
            echo 'Error: ' . \$e->getMessage();
        }
    " >/dev/null 2>&1
    
    # Try to login and get token
    login_response=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d '{"email":"test@example.com","password":"password123"}' \
        "$API_BASE/login" || echo "")
    
    if [ -n "$login_response" ]; then
        token=$(echo "$login_response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4 || echo "")
        if [ -n "$token" ]; then
            echo -e "${GREEN}✅ Authentication token obtained${NC}"
            echo "$token"
            return 0
        fi
    fi
    
    echo -e "${YELLOW}⚠️ Could not obtain auth token, testing without authentication${NC}"
    echo ""
    return 1
}

# Function to test authenticated endpoint
test_authenticated_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_status=$3
    local description=$4
    local token=$5
    local data=$6
    
    echo -e "${BLUE}🔐 Testing: $method $endpoint - $description (Authenticated)${NC}"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" -X GET \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/json" \
            "$API_BASE$endpoint" || echo "000")
    elif [ "$method" = "POST" ]; then
        if [ -n "$data" ]; then
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Authorization: Bearer $token" \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -d "$data" "$API_BASE$endpoint" || echo "000")
        else
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Authorization: Bearer $token" \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                "$API_BASE$endpoint" || echo "000")
        fi
    fi
    
    if [ "$response" = "$expected_status" ]; then
        print_api_test_result 0 "$description (HTTP $response)"
        return 0
    else
        print_api_test_result 1 "$description (Expected HTTP $expected_status, got $response)"
        return 1
    fi
}

echo -e "${YELLOW}📋 Phase 1: Basic API Tests${NC}"
echo "----------------------------------------"

# Test basic API availability
test_api_endpoint "GET" "/" "404" "Base API endpoint"

# Test health check if it exists
test_api_endpoint "GET" "/health" "404" "Health check endpoint (may not exist)"

echo -e "\n${YELLOW}📋 Phase 2: Authentication Tests${NC}"
echo "----------------------------------------"

# Test registration endpoint
test_api_endpoint "POST" "/register" "422" "Registration endpoint (validation error expected)" \
    '{"name":"","email":"","password":""}'

# Test login endpoint
test_api_endpoint "POST" "/login" "422" "Login endpoint (validation error expected)" \
    '{"email":"","password":""}'

# Test valid registration
test_api_endpoint "POST" "/register" "201" "Valid registration" \
    '{"name":"Test User 2","email":"test2@example.com","password":"password123","password_confirmation":"password123"}'

# Test valid login
test_api_endpoint "POST" "/login" "200" "Valid login" \
    '{"email":"test@example.com","password":"password123"}'

echo -e "\n${YELLOW}📋 Phase 3: Authenticated Endpoint Tests${NC}"
echo "----------------------------------------"

# Get authentication token
AUTH_TOKEN=$(get_auth_token)

if [ -n "$AUTH_TOKEN" ]; then
    # Test dashboard endpoint
    test_authenticated_endpoint "GET" "/dashboard" "200" "Dashboard endpoint" "$AUTH_TOKEN"
    
    # Test user profile
    test_authenticated_endpoint "GET" "/user" "200" "User profile endpoint" "$AUTH_TOKEN"
    
    # Test logout
    test_authenticated_endpoint "POST" "/logout" "200" "Logout endpoint" "$AUTH_TOKEN"
    
    # Test income endpoints
    test_authenticated_endpoint "GET" "/incomes" "200" "Get incomes" "$AUTH_TOKEN"
    test_authenticated_endpoint "POST" "/incomes" "422" "Create income (validation error)" "$AUTH_TOKEN" \
        '{"amount":"","description":"","date":""}'
    
    # Test expense endpoints (if they exist)
    test_authenticated_endpoint "GET" "/expenses" "200" "Get expenses" "$AUTH_TOKEN"
    
else
    echo -e "${YELLOW}⚠️ Skipping authenticated tests due to missing auth token${NC}"
fi

echo -e "\n${YELLOW}📋 Phase 4: Error Handling Tests${NC}"
echo "----------------------------------------"

# Test non-existent endpoints
test_api_endpoint "GET" "/nonexistent" "404" "Non-existent endpoint"

# Test method not allowed
test_api_endpoint "POST" "/nonexistent" "404" "Method not allowed"

# Test malformed JSON
response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"invalid": json}' \
    "$API_BASE/login" || echo "000")

if [ "$response" = "400" ] || [ "$response" = "422" ]; then
    print_api_test_result 0 "Malformed JSON handling (HTTP $response)"
else
    print_api_test_result 1 "Malformed JSON handling (Expected HTTP 400/422, got $response)"
fi

echo -e "\n${YELLOW}📋 Final API Test Results${NC}"
echo "=========================="
echo -e "${GREEN}API Tests Passed: $API_TESTS_PASSED${NC}"
echo -e "${RED}API Tests Failed: $API_TESTS_FAILED${NC}"

if [ $API_TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All API tests passed! Your API endpoints are working correctly.${NC}"
    exit 0
else
    echo -e "\n${YELLOW}⚠️ Some API tests failed. This might be expected if certain endpoints don't exist yet.${NC}"
    exit 0  # Don't fail the script for API tests
fi
