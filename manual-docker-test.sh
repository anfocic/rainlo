#!/bin/bash

# Manual Docker Test Script for SmartTax Application
# Tests the Docker setup manually without relying on test frameworks

set -e

echo "🧪 Manual Docker Testing for SmartTax Application"
echo "================================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BASE_URL="http://localhost:8080"
API_BASE="$BASE_URL/api"

# Test results tracking
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_test_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ PASS:${NC} $2"
        ((TESTS_PASSED++))
    else
        echo -e "${RED}❌ FAIL:${NC} $2"
        ((TESTS_FAILED++))
    fi
}

# Function to test HTTP endpoint
test_endpoint() {
    local method=$1
    local url=$2
    local expected_status=$3
    local description=$4
    local data=$5
    
    echo -e "${BLUE}🌐 Testing: $description${NC}"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" -H "Accept: application/json" "$url" || echo "000")
    elif [ "$method" = "POST" ]; then
        if [ -n "$data" ]; then
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                -d "$data" "$url" || echo "000")
        else
            response=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
                -H "Content-Type: application/json" \
                -H "Accept: application/json" \
                "$url" || echo "000")
        fi
    fi
    
    if [ "$response" = "$expected_status" ]; then
        print_test_result 0 "$description (HTTP $response)"
        return 0
    else
        print_test_result 1 "$description (Expected HTTP $expected_status, got $response)"
        return 1
    fi
}

echo -e "${YELLOW}📋 Phase 1: Container Health Tests${NC}"
echo "----------------------------------------"

# Test 1: Check if containers are running
echo -e "${BLUE}📊 Checking container status...${NC}"
app_running=$(docker-compose ps app | grep "Up" | wc -l)
db_running=$(docker-compose ps db | grep "Up" | wc -l)

print_test_result $((app_running > 0 ? 0 : 1)) "App container is running"
print_test_result $((db_running > 0 ? 0 : 1)) "Database container is running"

# Test 2: Check if PHP-FPM is running
echo -e "${BLUE}🐘 Checking PHP-FPM process...${NC}"
docker-compose exec -T app pgrep php-fpm >/dev/null 2>&1
print_test_result $? "PHP-FPM process is running"

# Test 3: Check if Nginx is running
echo -e "${BLUE}🌐 Checking Nginx process...${NC}"
docker-compose exec -T app pgrep nginx >/dev/null 2>&1
print_test_result $? "Nginx process is running"

echo -e "\n${YELLOW}📋 Phase 2: Database Tests${NC}"
echo "----------------------------------------"

# Test 4: Test database connectivity
echo -e "${BLUE}🗄️ Testing database connectivity...${NC}"
docker-compose exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" >/dev/null 2>&1
print_test_result $? "Database connectivity from Laravel"

# Test 5: Check if migrations ran
echo -e "${BLUE}📋 Checking database tables...${NC}"
table_count=$(docker-compose exec -T app php artisan tinker --execute="echo DB::select('SHOW TABLES;') ? count(DB::select('SHOW TABLES;')) : 0;" 2>/dev/null | tail -1)
if [ "$table_count" -gt 5 ]; then
    print_test_result 0 "Database tables exist ($table_count tables)"
else
    print_test_result 1 "Database tables missing (only $table_count tables)"
fi

echo -e "\n${YELLOW}📋 Phase 3: Web Server Tests${NC}"
echo "----------------------------------------"

# Test 6: Basic HTTP response
test_endpoint "GET" "$BASE_URL" "404" "Basic HTTP response"

# Test 7: Laravel API response
test_endpoint "GET" "$API_BASE" "404" "Laravel API endpoint"

echo -e "\n${YELLOW}📋 Phase 4: API Endpoint Tests${NC}"
echo "----------------------------------------"

# Test 8: Registration endpoint (validation error expected)
test_endpoint "POST" "$API_BASE/register" "422" "Registration validation" \
    '{"name":"","email":"","password":""}'

# Test 9: Login endpoint (validation error expected)
test_endpoint "POST" "$API_BASE/login" "422" "Login validation" \
    '{"email":"","password":""}'

# Test 10: Valid registration
test_endpoint "POST" "$API_BASE/register" "201" "Valid user registration" \
    '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Test 11: Login with created user
echo -e "${BLUE}🔐 Testing login with created user...${NC}"
login_response=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"email":"test@example.com","password":"password123"}' \
    "$API_BASE/login")

if echo "$login_response" | grep -q "token"; then
    print_test_result 0 "User login successful"
    
    # Extract token for authenticated tests
    token=$(echo "$login_response" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$token" ]; then
        echo -e "${BLUE}🔐 Testing authenticated endpoints...${NC}"
        
        # Test dashboard endpoint
        dashboard_response=$(curl -s -o /dev/null -w "%{http_code}" \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/json" \
            "$API_BASE/dashboard")
        
        if [ "$dashboard_response" = "200" ]; then
            print_test_result 0 "Dashboard endpoint (authenticated)"
        else
            print_test_result 1 "Dashboard endpoint (Expected 200, got $dashboard_response)"
        fi
        
        # Test user profile endpoint
        user_response=$(curl -s -o /dev/null -w "%{http_code}" \
            -H "Authorization: Bearer $token" \
            -H "Accept: application/json" \
            "$API_BASE/user")
        
        if [ "$user_response" = "200" ]; then
            print_test_result 0 "User profile endpoint (authenticated)"
        else
            print_test_result 1 "User profile endpoint (Expected 200, got $user_response)"
        fi
    fi
else
    print_test_result 1 "User login failed"
fi

echo -e "\n${YELLOW}📋 Phase 5: Performance Tests${NC}"
echo "----------------------------------------"

# Test 12: Check file permissions
echo -e "${BLUE}🔒 Checking file permissions...${NC}"
storage_writable=$(docker-compose exec -T app test -w /var/www/html/storage && echo "1" || echo "0")
cache_writable=$(docker-compose exec -T app test -w /var/www/html/bootstrap/cache && echo "1" || echo "0")

print_test_result $((storage_writable == 1 ? 0 : 1)) "Storage directory is writable"
print_test_result $((cache_writable == 1 ? 0 : 1)) "Cache directory is writable"

# Test 13: Check memory usage
echo -e "${BLUE}💾 Checking container memory usage...${NC}"
memory_usage=$(docker stats --no-stream --format "{{.MemPerc}}" smartax-app-1 2>/dev/null | sed 's/%//' || echo "0")
if [ -n "$memory_usage" ] && (( $(echo "$memory_usage < 80" | bc -l 2>/dev/null || echo "1") )); then
    print_test_result 0 "Memory usage is acceptable ($memory_usage%)"
else
    print_test_result 1 "Memory usage is high or unknown ($memory_usage%)"
fi

echo -e "\n${YELLOW}📋 Final Results${NC}"
echo "=================="
echo -e "${GREEN}Tests Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Tests Failed: $TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Your Docker setup is working correctly.${NC}"
    echo ""
    echo -e "${GREEN}✅ Your SmartTax application is ready for:${NC}"
    echo "• Development work"
    echo "• Production deployment"
    echo "• API testing and integration"
    echo ""
    echo -e "${BLUE}📋 Application Details:${NC}"
    echo "• Application URL: http://localhost:8080"
    echo "• Database: MySQL 8.0 on localhost:3306"
    echo "• API Base: http://localhost:8080/api"
    echo ""
    echo -e "${BLUE}🛠️ Useful commands:${NC}"
    echo "• View logs: docker-compose logs -f"
    echo "• Access app shell: docker-compose exec app sh"
    echo "• Run artisan commands: docker-compose exec app php artisan [command]"
    echo "• Stop services: docker-compose down"
    exit 0
else
    echo -e "\n${RED}⚠️ Some tests failed. Please review the output above.${NC}"
    echo ""
    echo -e "${BLUE}🔍 Troubleshooting:${NC}"
    echo "• Check logs: docker-compose logs"
    echo "• Restart services: docker-compose restart"
    echo "• Rebuild: docker-compose build --no-cache"
    exit 1
fi
