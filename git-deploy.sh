#!/bin/bash

# Git-based deployment script for Rainlo
# Run this script on your server after pulling changes from GitHub
# This script preserves important files and handles migrations safely

set -e

PROJECT_PATH="/opt/rainlo"
BACKUP_PATH="/opt/rainlo-backup-$(date +%Y%m%d-%H%M%S)"

echo "🚀 Starting Git-based deployment..."

# Navigate to project directory
cd "$PROJECT_PATH" || {
    echo "❌ Project directory not found at $PROJECT_PATH"
    echo "Please ensure the repository is cloned to $PROJECT_PATH first"
    exit 1
}

# Verify we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Not a git repository. Please clone the repository first."
    exit 1
fi

# Create backup of important files before deployment
echo "📦 Creating backup of important files..."
mkdir -p "$BACKUP_PATH"
[ -f ".env" ] && cp .env "$BACKUP_PATH/.env.backup" || echo "No .env file to backup"
[ -d "storage" ] && cp -r storage "$BACKUP_PATH/storage.backup" || echo "No storage directory to backup"
echo "✅ Backup created at $BACKUP_PATH"

# Check current git status
echo "📊 Checking git status..."
git status --porcelain

# Stash any local changes to preserve important files
echo "💾 Stashing local changes..."
git stash push -m "Pre-deployment stash $(date)"

# Pull latest changes safely
echo "📥 Pulling latest changes..."
git fetch origin
git merge origin/master || {
    echo "❌ Git merge failed. Rolling back..."
    git stash pop || true
    exit 1
}

# Set up environment first (before dependencies)
echo "⚙️ Setting up environment..."
if [ -f "$BACKUP_PATH/.env.backup" ]; then
    echo "Restoring previous .env file..."
    cp "$BACKUP_PATH/.env.backup" .env
    echo "✅ Previous .env file restored"
elif [ ! -f ".env" ] && [ -f ".env.production" ]; then
    echo "Creating .env from .env.production..."
    cp .env.production .env
    echo "✅ Environment file created from .env.production"
elif [ ! -f ".env" ]; then
    echo "❌ No .env file found and no .env.production template available"
    exit 1
fi

# Install/update dependencies using Docker
echo "📦 Installing dependencies..."
if [ -f "composer.json" ]; then
    echo "Installing PHP dependencies with Docker..."
    docker run --rm -v "$PWD":/app composer:latest install --no-dev --optimize-autoloader --working-dir=/app
fi

if [ -f "package.json" ]; then
    echo "Installing Node dependencies with Docker..."
    docker run --rm -v "$PWD":/app -w /app node:18-alpine npm ci --production
fi

# Set proper permissions first
echo "🔧 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R 1000:1000 storage bootstrap/cache 2>/dev/null || true

# Stop existing containers to avoid conflicts
echo "🛑 Stopping existing containers..."
if [ -f "docker-compose.prod.yml" ]; then
    docker-compose -f docker-compose.prod.yml down --remove-orphans 2>/dev/null || true
elif [ -f "docker-compose.yml" ]; then
    docker-compose down --remove-orphans 2>/dev/null || true
fi

# Laravel specific commands using Docker
if [ -f "artisan" ]; then
    echo "🔧 Running Laravel deployment..."

    # Determine which docker-compose file to use
    COMPOSE_FILE=""
    if [ -f "docker-compose.prod.yml" ]; then
        COMPOSE_FILE="docker-compose.prod.yml"
        echo "Using production docker-compose configuration"
    elif [ -f "docker-compose.yml" ]; then
        COMPOSE_FILE="docker-compose.yml"
        echo "Using development docker-compose configuration"
    else
        echo "❌ No docker-compose configuration found"
        exit 1
    fi

    # Start containers
    echo "🚀 Starting Docker containers..."
    docker-compose -f "$COMPOSE_FILE" up -d --build --remove-orphans

    # Wait for containers to be ready
    echo "⏳ Waiting for containers to be ready..."
    sleep 10

    # Check if database is accessible
    echo "🔍 Checking database connectivity..."
    for i in {1..30}; do
        if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" 2>/dev/null; then
            echo "✅ Database connection successful"
            break
        else
            echo "⏳ Waiting for database... (attempt $i/30)"
            sleep 2
        fi

        if [ $i -eq 30 ]; then
            echo "❌ Database connection failed after 30 attempts"
            echo "Container logs:"
            docker-compose -f "$COMPOSE_FILE" logs --tail=20
            exit 1
        fi
    done

    # Run Laravel commands inside the container
    echo "🔧 Running Laravel artisan commands..."

    # Clear caches first
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan config:clear || echo "⚠️ Config clear failed"
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan route:clear || echo "⚠️ Route clear failed"
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan view:clear || echo "⚠️ View clear failed"

    # Run migrations
    echo "🗄️ Running database migrations..."
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force || {
        echo "❌ Migration failed. Check database configuration."
        docker-compose -f "$COMPOSE_FILE" logs app --tail=20
        exit 1
    }

    # Cache configurations
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache || echo "⚠️ Config cache failed"
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache || echo "⚠️ Route cache failed"
    docker-compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache || echo "⚠️ View cache failed"

    echo "✅ Laravel commands completed successfully"
fi

# Final status check and cleanup
echo "🔍 Final deployment verification..."

# Show container status
if [ -n "$COMPOSE_FILE" ]; then
    echo "📊 Container status:"
    docker-compose -f "$COMPOSE_FILE" ps

    # Test application health
    echo "🏥 Testing application health..."
    sleep 5
    if docker-compose -f "$COMPOSE_FILE" exec -T app php artisan route:list >/dev/null 2>&1; then
        echo "✅ Application is responding correctly"
    else
        echo "⚠️ Application may have issues. Check logs:"
        docker-compose -f "$COMPOSE_FILE" logs app --tail=10
    fi
fi

# Clean up git stash if deployment was successful
echo "🧹 Cleaning up git stash..."
git stash drop 2>/dev/null || echo "No stash to clean up"

echo "🎉 Deployment completed successfully!"
echo "📍 Backup location: $BACKUP_PATH"
echo "🌐 Your application should now be live at https://api.rainlo.app"

# Optional: Clean up old backups (keep last 5)
echo "🧹 Cleaning up old backups..."
ls -t /opt/rainlo-backup-* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

echo "✅ All done!"
echo ""
echo "📋 Deployment Summary:"
echo "   - Project path: $PROJECT_PATH"
echo "   - Backup created: $BACKUP_PATH"
echo "   - Docker compose file: $COMPOSE_FILE"
echo "   - Database migrations: ✅ Completed"
echo "   - Application caches: ✅ Rebuilt"
echo ""
echo "🔧 Useful commands:"
echo "   - View logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "   - Check status: docker-compose -f $COMPOSE_FILE ps"
echo "   - Access container: docker-compose -f $COMPOSE_FILE exec app bash"
