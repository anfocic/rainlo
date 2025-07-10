#!/bin/bash

# SmartTax Production Deployment Script for Home Server
# This script should be placed on your home server at /opt/smartax/deploy.sh

set -e

echo "🚀 Starting SmartTax deployment on home server..."

# Configuration
DEPLOY_DIR="/opt/smartax"
BACKUP_DIR="/opt/smartax/backups"
LOG_FILE="/opt/smartax/deploy.log"
IMAGE_NAME="ghcr.io/anfocic/smartax:latest"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1" | tee -a "$LOG_FILE"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

# Create necessary directories
mkdir -p "$BACKUP_DIR"
mkdir -p "$DEPLOY_DIR/nginx"
mkdir -p "$DEPLOY_DIR/mysql-init"

# Change to deployment directory
cd "$DEPLOY_DIR" || error "Failed to change to deployment directory"

# Load environment variables
if [ -f ".env.production" ]; then
    log "Loading production environment variables..."
    export $(cat .env.production | grep -v '^#' | xargs)
else
    warning "No .env.production file found. Using defaults."
fi

# Set default values if not provided
export IMAGE_TAG="${IMAGE_TAG:-$IMAGE_NAME}"
export DB_PASSWORD="${DB_PASSWORD:-$(openssl rand -base64 32)}"
export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-$(openssl rand -base64 32)}"
export APP_KEY="${APP_KEY:-base64:$(openssl rand -base64 32)}"
export APP_URL="${APP_URL:-http://localhost:8080}"

log "Using image: $IMAGE_TAG"

# Backup current database if containers are running
if docker-compose -f docker-compose.prod.yml ps db | grep -q "Up"; then
    log "Creating database backup..."
    BACKUP_FILE="$BACKUP_DIR/smartax_backup_$(date +%Y%m%d_%H%M%S).sql"
    docker-compose -f docker-compose.prod.yml exec -T db mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" smartax > "$BACKUP_FILE" || warning "Database backup failed"
    success "Database backup created: $BACKUP_FILE"
fi

# Pull the latest image
log "Pulling latest Docker image..."
docker pull "$IMAGE_TAG" || error "Failed to pull Docker image"

# Stop current containers gracefully
log "Stopping current containers..."
docker-compose -f docker-compose.prod.yml down --timeout 30 || warning "Some containers may not have stopped gracefully"

# Prune old images to save space
log "Cleaning up old Docker images..."
docker image prune -f || warning "Failed to prune old images"

# Start new containers
log "Starting new containers..."
docker-compose -f docker-compose.prod.yml up -d || error "Failed to start containers"

# Wait for database to be ready
log "Waiting for database to be ready..."
for i in {1..30}; do
    if docker-compose -f docker-compose.prod.yml exec -T db mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD" --silent; then
        success "Database is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        error "Database failed to start within 5 minutes"
    fi
    sleep 10
done

# Wait for application to be ready
log "Waiting for application to be ready..."
for i in {1..20}; do
    if docker-compose -f docker-compose.prod.yml exec -T app php artisan --version > /dev/null 2>&1; then
        success "Application is ready"
        break
    fi
    if [ $i -eq 20 ]; then
        error "Application failed to start within 3 minutes"
    fi
    sleep 10
done

# Run database migrations
log "Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force || error "Database migrations failed"

# Clear and cache configuration
log "Optimizing application..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache || warning "Config cache failed"
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache || warning "Route cache failed"
docker-compose -f docker-compose.prod.yml exec -T app php artisan view:cache || warning "View cache failed"

# Health check
log "Performing health check..."
sleep 10
if curl -f -s "http://localhost:8080/up" > /dev/null; then
    success "Health check passed"
else
    error "Health check failed - application may not be working correctly"
fi

# Display container status
log "Container status:"
docker-compose -f docker-compose.prod.yml ps

# Save environment variables for next deployment
cat > .env.production << EOF
# Generated on $(date)
IMAGE_TAG=$IMAGE_TAG
DB_PASSWORD=$DB_PASSWORD
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
APP_KEY=$APP_KEY
APP_URL=$APP_URL
EOF

success "🎉 Deployment completed successfully!"
log "Application is available at: $APP_URL"
log "Database backups are stored in: $BACKUP_DIR"
log "Deployment logs are stored in: $LOG_FILE"

# Optional: Send notification (uncomment and configure as needed)
# curl -X POST -H 'Content-type: application/json' \
#     --data '{"text":"SmartTax deployment completed successfully!"}' \
#     YOUR_SLACK_WEBHOOK_URL
