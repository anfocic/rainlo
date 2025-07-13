#!/bin/bash

# 🚀 Rainlo API Production Deployment Script
# This script deploys your Rainlo API using Docker Hub images

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
COMPOSE_FILE="docker-compose.prod.yml"
IMAGE_NAME="fole/rainlo-api:latest"
PROJECT_NAME="rainlo"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_requirements() {
    log_info "Checking requirements..."
    
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed"
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        log_error "Docker Compose is not installed"
        exit 1
    fi
    
    if [ ! -f "$COMPOSE_FILE" ]; then
        log_error "$COMPOSE_FILE not found"
        exit 1
    fi
    
    if [ ! -f ".env.production" ]; then
        log_error ".env.production not found"
        exit 1
    fi
    
    log_success "All requirements met"
}

pull_latest_image() {
    log_info "Pulling latest image from Docker Hub..."
    docker pull $IMAGE_NAME
    log_success "Image pulled successfully"
}

stop_existing_containers() {
    log_info "Stopping existing containers..."
    docker-compose -f $COMPOSE_FILE down || true
    log_success "Containers stopped"
}

start_database() {
    log_info "Starting database..."
    docker-compose -f $COMPOSE_FILE up -d db
    
    # Wait for database to be ready
    log_info "Waiting for database to be ready..."
    sleep 10
    
    # Check if database is ready
    for i in {1..30}; do
        if docker-compose -f $COMPOSE_FILE exec -T db pg_isready -U rainlo > /dev/null 2>&1; then
            log_success "Database is ready"
            break
        fi
        if [ $i -eq 30 ]; then
            log_error "Database failed to start"
            exit 1
        fi
        sleep 2
    done
}

run_migrations() {
    log_info "Running database migrations..."
    docker-compose -f $COMPOSE_FILE run --rm app php artisan migrate --force
    log_success "Migrations completed"
}

seed_database() {
    log_info "Seeding database with test data..."
    docker-compose -f $COMPOSE_FILE run --rm app php artisan db:seed --class=DockerTestDataSeeder --force
    log_success "Database seeded"
}

start_application() {
    log_info "Starting application..."
    docker-compose -f $COMPOSE_FILE up -d app
    log_success "Application started"
}

wait_for_app() {
    log_info "Waiting for application to be ready..."
    sleep 15
    
    # Check if app is responding
    for i in {1..30}; do
        if docker-compose -f $COMPOSE_FILE exec -T app php artisan route:list > /dev/null 2>&1; then
            log_success "Application is ready"
            break
        fi
        if [ $i -eq 30 ]; then
            log_warning "Application might not be fully ready, but continuing..."
            break
        fi
        sleep 2
    done
}

show_status() {
    log_info "Deployment Status:"
    echo ""
    docker-compose -f $COMPOSE_FILE ps
    echo ""
    
    log_info "Application URLs:"
    echo "🌐 API Base URL: https://api.rainlo.app/api/"
    echo "❤️  Health Check: https://api.rainlo.app/api/health"
    echo "🗄️  Database Check: https://api.rainlo.app/api/health/database"
    echo ""
    
    log_info "Useful Commands:"
    echo "📋 View logs: docker-compose -f $COMPOSE_FILE logs -f app"
    echo "🔄 Restart: docker-compose -f $COMPOSE_FILE restart"
    echo "🛑 Stop: docker-compose -f $COMPOSE_FILE down"
    echo "🗄️  Database: docker-compose -f $COMPOSE_FILE exec db psql -U rainlo -d rainlo"
}

cleanup_old_images() {
    log_info "Cleaning up old Docker images..."
    docker image prune -f || true
    log_success "Cleanup completed"
}

# Main deployment process
main() {
    echo ""
    log_info "🚀 Starting Rainlo API Deployment"
    echo "=================================="
    
    check_requirements
    pull_latest_image
    stop_existing_containers
    start_database
    run_migrations
    seed_database
    start_application
    wait_for_app
    cleanup_old_images
    
    echo ""
    log_success "🎉 Deployment completed successfully!"
    echo "=================================="
    show_status
}

# Handle script arguments
case "${1:-deploy}" in
    "deploy")
        main
        ;;
    "status")
        docker-compose -f $COMPOSE_FILE ps
        ;;
    "logs")
        docker-compose -f $COMPOSE_FILE logs -f app
        ;;
    "restart")
        log_info "Restarting application..."
        docker-compose -f $COMPOSE_FILE restart
        log_success "Application restarted"
        ;;
    "stop")
        log_info "Stopping application..."
        docker-compose -f $COMPOSE_FILE down
        log_success "Application stopped"
        ;;
    "update")
        log_info "Updating application..."
        pull_latest_image
        docker-compose -f $COMPOSE_FILE up -d app
        log_success "Application updated"
        ;;
    "db")
        docker-compose -f $COMPOSE_FILE exec db psql -U rainlo -d rainlo
        ;;
    "help")
        echo "Rainlo API Deployment Script"
        echo ""
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  deploy    Full deployment (default)"
        echo "  status    Show container status"
        echo "  logs      Show application logs"
        echo "  restart   Restart application"
        echo "  stop      Stop application"
        echo "  update    Pull latest image and restart"
        echo "  db        Connect to database"
        echo "  help      Show this help"
        ;;
    *)
        log_error "Unknown command: $1"
        echo "Use '$0 help' for available commands"
        exit 1
        ;;
esac
