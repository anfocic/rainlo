#!/bin/bash

# =============================================================================
# Rainlo Docker Database Management Scripts
# =============================================================================
# Collection of useful bash commands for managing Docker database and debugging
# Usage: ./docker-db-scripts.sh <command> [args]
# =============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# =============================================================================
# DOCKER CONTAINER MANAGEMENT
# =============================================================================

start_services() {
    echo -e "${BLUE}🚀 Starting Docker services...${NC}"
    docker-compose up -d
    echo -e "${GREEN}✅ Services started${NC}"
}

stop_services() {
    echo -e "${YELLOW}🛑 Stopping Docker services...${NC}"
    docker-compose down
    echo -e "${GREEN}✅ Services stopped${NC}"
}

restart_services() {
    echo -e "${BLUE}🔄 Restarting Docker services...${NC}"
    docker-compose down
    docker-compose up -d
    echo -e "${GREEN}✅ Services restarted${NC}"
}

service_status() {
    echo -e "${CYAN}📊 Docker services status:${NC}"
    docker-compose ps
}

# =============================================================================
# DATABASE OPERATIONS
# =============================================================================

seed_docker_db() {
    echo -e "${GREEN}🌱 Seeding Docker database with test data...${NC}"
    docker-compose exec app php artisan migrate:fresh --force
    docker-compose exec app php artisan db:seed --class=DockerTestDataSeeder
    echo -e "${GREEN}✅ Database seeded successfully${NC}"
}

seed_local_db() {
    echo -e "${GREEN}🌱 Seeding database with local test data...${NC}"
    docker-compose exec app php artisan migrate:fresh --force
    docker-compose exec app php artisan db:seed --class=TestDataSeeder
    echo -e "${GREEN}✅ Database seeded successfully${NC}"
}

fresh_migrate() {
    echo -e "${BLUE}🔄 Running fresh migrations...${NC}"
    docker-compose exec app php artisan migrate:fresh --force
    echo -e "${GREEN}✅ Fresh migrations completed${NC}"
}

migrate() {
    echo -e "${BLUE}🔄 Running migrations...${NC}"
    docker-compose exec app php artisan migrate --force
    echo -e "${GREEN}✅ Migrations completed${NC}"
}

rollback_migration() {
    echo -e "${YELLOW}⏪ Rolling back last migration...${NC}"
    docker-compose exec app php artisan migrate:rollback --force
    echo -e "${GREEN}✅ Migration rolled back${NC}"
}

# =============================================================================
# DATABASE INSPECTION
# =============================================================================

connect_db() {
    echo -e "${CYAN}🔗 Connecting to PostgreSQL database...${NC}"
    docker-compose exec db psql -U ${DB_USERNAME:-rainlo} -d ${DB_DATABASE:-rainlo}
}

show_tables() {
    echo -e "${CYAN}📋 Database tables:${NC}"
    docker-compose exec db psql -U ${DB_USERNAME:-rainlo} -d ${DB_DATABASE:-rainlo} -c "\dt"
}

describe_table() {
    if [ -z "$2" ]; then
        echo -e "${RED}❌ Usage: ./docker-db-scripts.sh describe_table <table_name>${NC}"
        exit 1
    fi
    echo -e "${CYAN}📋 Structure of table '$2':${NC}"
    docker-compose exec db psql -U ${DB_USERNAME:-rainlo} -d ${DB_DATABASE:-rainlo} -c "\d $2"
}

count_records() {
    echo -e "${CYAN}📊 Record counts:${NC}"
    docker-compose exec app php artisan tinker --execute="
        echo 'Users: ' . App\Models\User::count() . PHP_EOL;
        echo 'Transactions: ' . App\Models\Transaction::count() . PHP_EOL;
        echo 'Income Transactions: ' . App\Models\Transaction::where('type', 'income')->count() . PHP_EOL;
        echo 'Expense Transactions: ' . App\Models\Transaction::where('type', 'expense')->count() . PHP_EOL;
    "
}

# =============================================================================
# DATABASE CLEANUP
# =============================================================================

wipe_database() {
    echo -e "${RED}⚠️  WARNING: This will completely wipe the database!${NC}"
    read -p "Are you sure? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${RED}🗑️  Wiping database...${NC}"
        docker-compose exec db psql -U ${DB_USERNAME:-rainlo} -d ${DB_DATABASE:-rainlo} -c "
            DROP SCHEMA public CASCADE;
            CREATE SCHEMA public;
            GRANT ALL ON SCHEMA public TO ${DB_USERNAME:-rainlo};
            GRANT ALL ON SCHEMA public TO public;
        "
        echo -e "${GREEN}✅ Database wiped clean${NC}"
    else
        echo -e "${YELLOW}❌ Operation cancelled${NC}"
    fi
}

reset_database() {
    echo -e "${BLUE}🔄 Resetting database (wipe + migrate + seed)...${NC}"
    wipe_database
    if [ $? -eq 0 ]; then
        fresh_migrate
        seed_docker_db
        echo -e "${GREEN}✅ Database reset completed${NC}"
    fi
}

# =============================================================================
# DEBUGGING & LOGS
# =============================================================================

app_logs() {
    echo -e "${CYAN}📋 Application logs:${NC}"
    docker-compose logs -f app
}

db_logs() {
    echo -e "${CYAN}📋 Database logs:${NC}"
    docker-compose logs -f db
}

all_logs() {
    echo -e "${CYAN}📋 All service logs:${NC}"
    docker-compose logs -f
}

clear_laravel_logs() {
    echo -e "${YELLOW}🧹 Clearing Laravel logs...${NC}"
    docker-compose exec app sh -c "rm -f storage/logs/*.log"
    echo -e "${GREEN}✅ Laravel logs cleared${NC}"
}

# =============================================================================
# LARAVEL COMMANDS
# =============================================================================

tinker() {
    echo -e "${CYAN}🔧 Opening Laravel Tinker...${NC}"
    docker-compose exec app php artisan tinker
}

clear_cache() {
    echo -e "${YELLOW}🧹 Clearing all Laravel caches...${NC}"
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    echo -e "${GREEN}✅ All caches cleared${NC}"
}

run_tests() {
    echo -e "${BLUE}🧪 Running tests...${NC}"
    docker-compose exec app php artisan test
}

run_test() {
    if [ -z "$2" ]; then
        echo -e "${RED}❌ Usage: ./docker-db-scripts.sh run_test <test_file>${NC}"
        exit 1
    fi
    echo -e "${BLUE}🧪 Running test: $2${NC}"
    docker-compose exec app php artisan test "$2"
}

# =============================================================================
# UTILITY FUNCTIONS
# =============================================================================

show_help() {
    echo -e "${PURPLE}==============================================================================${NC}"
    echo -e "${PURPLE}Rainlo Docker Database Management Scripts${NC}"
    echo -e "${PURPLE}==============================================================================${NC}"
    echo ""
    echo -e "${CYAN}Container Management:${NC}"
    echo "  ./docker-db-scripts.sh start_services      - Start all Docker services"
    echo "  ./docker-db-scripts.sh stop_services       - Stop all Docker services"
    echo "  ./docker-db-scripts.sh restart_services    - Restart all Docker services"
    echo "  ./docker-db-scripts.sh service_status      - Show service status"
    echo ""
    echo -e "${CYAN}Database Operations:${NC}"
    echo "  ./docker-db-scripts.sh seed_docker_db      - Seed with Docker test data"
    echo "  ./docker-db-scripts.sh seed_local_db       - Seed with local test data"
    echo "  ./docker-db-scripts.sh fresh_migrate       - Run fresh migrations"
    echo "  ./docker-db-scripts.sh migrate             - Run migrations"
    echo "  ./docker-db-scripts.sh rollback_migration  - Rollback last migration"
    echo ""
    echo -e "${CYAN}Database Inspection:${NC}"
    echo "  ./docker-db-scripts.sh connect_db          - Connect to PostgreSQL"
    echo "  ./docker-db-scripts.sh show_tables         - Show all tables"
    echo "  ./docker-db-scripts.sh describe_table <name> - Show table structure"
    echo "  ./docker-db-scripts.sh count_records       - Count records in main tables"
    echo ""
    echo -e "${CYAN}Database Cleanup:${NC}"
    echo "  ./docker-db-scripts.sh wipe_database       - Completely wipe database"
    echo "  ./docker-db-scripts.sh reset_database      - Wipe + migrate + seed"
    echo ""
    echo -e "${CYAN}Debugging & Logs:${NC}"
    echo "  ./docker-db-scripts.sh app_logs            - View application logs"
    echo "  ./docker-db-scripts.sh db_logs             - View database logs"
    echo "  ./docker-db-scripts.sh all_logs            - View all service logs"
    echo "  ./docker-db-scripts.sh clear_laravel_logs  - Clear Laravel log files"
    echo ""
    echo -e "${CYAN}Laravel Commands:${NC}"
    echo "  ./docker-db-scripts.sh tinker              - Open Laravel Tinker"
    echo "  ./docker-db-scripts.sh clear_cache         - Clear all Laravel caches"
    echo "  ./docker-db-scripts.sh run_tests           - Run all tests"
    echo "  ./docker-db-scripts.sh run_test <file>     - Run specific test file"
    echo ""
    echo -e "${CYAN}Usage Examples:${NC}"
    echo "  ./docker-db-scripts.sh seed_docker_db"
    echo "  ./docker-db-scripts.sh reset_database"
    echo "  ./docker-db-scripts.sh describe_table users"
    echo ""
}

# =============================================================================
# COMMAND DISPATCHER
# =============================================================================

# Check if command is provided
if [ $# -eq 0 ]; then
    show_help
    exit 0
fi

# Execute the requested command
case "$1" in
    "start_services")
        start_services
        ;;
    "stop_services")
        stop_services
        ;;
    "restart_services")
        restart_services
        ;;
    "service_status")
        service_status
        ;;
    "seed_docker_db")
        seed_docker_db
        ;;
    "seed_local_db")
        seed_local_db
        ;;
    "fresh_migrate")
        fresh_migrate
        ;;
    "migrate")
        migrate
        ;;
    "rollback_migration")
        rollback_migration
        ;;
    "connect_db")
        connect_db
        ;;
    "show_tables")
        show_tables
        ;;
    "describe_table")
        describe_table "$@"
        ;;
    "count_records")
        count_records
        ;;
    "wipe_database")
        wipe_database
        ;;
    "reset_database")
        reset_database
        ;;
    "app_logs")
        app_logs
        ;;
    "db_logs")
        db_logs
        ;;
    "all_logs")
        all_logs
        ;;
    "clear_laravel_logs")
        clear_laravel_logs
        ;;
    "tinker")
        tinker
        ;;
    "clear_cache")
        clear_cache
        ;;
    "run_tests")
        run_tests
        ;;
    "run_test")
        run_test "$@"
        ;;
    "help"|"--help"|"-h")
        show_help
        ;;
    *)
        echo -e "${RED}❌ Unknown command: $1${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac
