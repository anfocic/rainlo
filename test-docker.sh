#!/bin/bash

# Docker Testing Script for SmartTax Laravel Application
# This script performs comprehensive testing of the Docker setup

set -e  # Exit on any error

echo "🐳 Starting Docker Testing for SmartTax Application"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Function to wait for service to be ready
wait_for_service() {
    local service=$1
    local max_attempts=30
    local attempt=1
    
    echo -e "${BLUE}⏳ Waiting for $service to be ready...${NC}"
    
    while [ $attempt -le $max_attempts ]; do
        if docker-compose exec -T $service echo "Service is ready" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ $service is ready${NC}"
            return 0
        fi
        echo "Attempt $attempt/$max_attempts - waiting for $service..."
        sleep 2
        ((attempt++))
    done
    
    echo -e "${RED}❌ $service failed to start within timeout${NC}"
    return 1
}

# Function to test HTTP endpoint
test_http_endpoint() {
    local url=$1
    local expected_status=$2
    local description=$3
    
    echo -e "${BLUE}🌐 Testing: $description${NC}"
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    
    if [ "$response" = "$expected_status" ]; then
        print_test_result 0 "$description (HTTP $response)"
        return 0
    else
        print_test_result 1 "$description (Expected HTTP $expected_status, got $response)"
        return 1
    fi
}

echo -e "${YELLOW}📋 Phase 1: Build and Container Tests${NC}"
echo "----------------------------------------"

# Test 1: Clean up any existing containers
echo -e "${BLUE}🧹 Cleaning up existing containers...${NC}"
docker-compose down -v --remove-orphans >/dev/null 2>&1 || true
print_test_result $? "Clean up existing containers"

# Test 2: Build the Docker image
echo -e "${BLUE}🔨 Building Docker image...${NC}"
docker-compose build --no-cache
print_test_result $? "Build Docker image"

# Test 3: Start services
echo -e "${BLUE}🚀 Starting services...${NC}"
docker-compose up -d
print_test_result $? "Start Docker Compose services"

# Test 4: Wait for database to be ready
wait_for_service db
print_test_result $? "Database service startup"

# Test 5: Wait for app to be ready
wait_for_service app
print_test_result $? "Application service startup"

# Test 6: Check if containers are running
echo -e "${BLUE}📊 Checking container status...${NC}"
app_running=$(docker-compose ps app | grep "Up" | wc -l)
db_running=$(docker-compose ps db | grep "Up" | wc -l)

print_test_result $((app_running > 0 ? 0 : 1)) "App container is running"
print_test_result $((db_running > 0 ? 0 : 1)) "Database container is running"

# Test 7: Check if ports are accessible
echo -e "${BLUE}🔌 Testing port accessibility...${NC}"
sleep 5  # Give services time to fully start

# Test if port 8080 is accessible
nc -z localhost 8080 >/dev/null 2>&1
print_test_result $? "Port 8080 is accessible"

echo -e "\n${YELLOW}📋 Phase 2: Application Health Tests${NC}"
echo "----------------------------------------"

# Test 8: Test basic HTTP response
test_http_endpoint "http://localhost:8080" "200" "Basic HTTP response"

# Test 9: Test Laravel application loads
test_http_endpoint "http://localhost:8080/api" "404" "Laravel API endpoint (404 expected for base /api)"

# Test 10: Check if PHP-FPM is running
echo -e "${BLUE}🐘 Checking PHP-FPM process...${NC}"
docker-compose exec -T app pgrep php-fpm >/dev/null 2>&1
print_test_result $? "PHP-FPM process is running"

# Test 11: Check if Nginx is running
echo -e "${BLUE}🌐 Checking Nginx process...${NC}"
docker-compose exec -T app pgrep nginx >/dev/null 2>&1
print_test_result $? "Nginx process is running"

# Test 12: Test database connectivity
echo -e "${BLUE}🗄️ Testing database connectivity...${NC}"
docker-compose exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" >/dev/null 2>&1
print_test_result $? "Database connectivity from Laravel"

# Test 13: Check Laravel configuration
echo -e "${BLUE}⚙️ Checking Laravel configuration...${NC}"
docker-compose exec -T app php artisan config:show app.name >/dev/null 2>&1
print_test_result $? "Laravel configuration is accessible"

echo -e "\n${YELLOW}📋 Phase 3: Laravel Application Tests${NC}"
echo "----------------------------------------"

# Test 14: Run database migrations
echo -e "${BLUE}🗄️ Running database migrations...${NC}"
docker-compose exec -T app php artisan migrate:fresh --force >/dev/null 2>&1
print_test_result $? "Database migrations"

# Test 15: Run Laravel tests
echo -e "${BLUE}🧪 Running Laravel tests...${NC}"
docker-compose exec -T app php artisan test --stop-on-failure >/dev/null 2>&1
print_test_result $? "Laravel application tests"

echo -e "\n${YELLOW}📋 Phase 4: Performance and Security Tests${NC}"
echo "----------------------------------------"

# Test 16: Check file permissions
echo -e "${BLUE}🔒 Checking file permissions...${NC}"
storage_writable=$(docker-compose exec -T app test -w /var/www/html/storage && echo "1" || echo "0")
cache_writable=$(docker-compose exec -T app test -w /var/www/html/bootstrap/cache && echo "1" || echo "0")

print_test_result $((storage_writable == 1 ? 0 : 1)) "Storage directory is writable"
print_test_result $((cache_writable == 1 ? 0 : 1)) "Cache directory is writable"

# Test 17: Test container restart
echo -e "${BLUE}🔄 Testing container restart...${NC}"
docker-compose restart app >/dev/null 2>&1
sleep 10
docker-compose exec -T app echo "Container restarted successfully" >/dev/null 2>&1
print_test_result $? "Container restart functionality"

# Test 18: Check memory usage
echo -e "${BLUE}💾 Checking container memory usage...${NC}"
memory_usage=$(docker stats --no-stream --format "{{.MemPerc}}" smartax-app-1 | sed 's/%//')
if (( $(echo "$memory_usage < 80" | bc -l) )); then
    print_test_result 0 "Memory usage is acceptable ($memory_usage%)"
else
    print_test_result 1 "Memory usage is high ($memory_usage%)"
fi

echo -e "\n${YELLOW}📋 Final Results${NC}"
echo "=================="
echo -e "${GREEN}Tests Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Tests Failed: $TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Your Docker setup is working correctly.${NC}"
    exit 0
else
    echo -e "\n${RED}⚠️ Some tests failed. Please check the output above for details.${NC}"
    exit 1
fi
