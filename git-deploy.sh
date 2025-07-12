#!/bin/bash

# Simple Git-based deployment script
# Run this script on your server to deploy the latest changes

set -e

PROJECT_PATH="/home/andrej/rainlo"
BACKUP_PATH="/home/andrej/rainlo-backup-$(date +%Y%m%d-%H%M%S)"

echo "🚀 Starting Git-based deployment..."

# Navigate to project directory
cd "$PROJECT_PATH" || {
    echo "❌ Project directory not found. Cloning repository..."
    git clone https://github.com/anfocic/rainlo.git "$PROJECT_PATH"
    cd "$PROJECT_PATH"
}

# Create backup
echo "📦 Creating backup..."
cp -r "$PROJECT_PATH" "$BACKUP_PATH"
echo "✅ Backup created at $BACKUP_PATH"

# Pull latest changes
echo "📥 Pulling latest changes..."
git fetch origin
git reset --hard origin/master  # Force update to match remote

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

# Set up environment
echo "⚙️ Setting up environment..."
if [ ! -f ".env" ] && [ -f ".env.production" ]; then
    cp .env.production .env
    echo "✅ Environment file created from .env.production"
fi

# Set proper permissions first
echo "🔧 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R 1000:1000 storage bootstrap/cache 2>/dev/null || true

# Laravel specific commands using Docker
if [ -f "artisan" ]; then
    echo "🔧 Running Laravel commands..."

    # Start containers first
    if [ -f "docker-compose.yml" ]; then
        echo "Starting Docker containers..."
        docker-compose up -d

        # Wait a moment for containers to be ready
        sleep 5

        # Run Laravel commands inside the container
        echo "Running Laravel artisan commands..."
        docker-compose exec -T app php artisan config:cache || echo "Config cache failed"
        docker-compose exec -T app php artisan route:cache || echo "Route cache failed"
        docker-compose exec -T app php artisan view:cache || echo "View cache failed"
        docker-compose exec -T app php artisan migrate --force || echo "Migration failed"
    else
        echo "No docker-compose.yml found, skipping Laravel commands"
    fi
fi

# Final restart of services
echo "🔄 Final restart of services..."
if [ -f "docker-compose.yml" ]; then
    echo "Rebuilding and restarting containers..."
    docker-compose down
    docker-compose up -d --build

    # Show container status
    echo "Container status:"
    docker-compose ps
elif systemctl is-active --quiet nginx 2>/dev/null; then
    sudo systemctl reload nginx
fi

echo "🎉 Deployment completed successfully!"
echo "📍 Backup location: $BACKUP_PATH"
echo "🌐 Your application should now be live!"

# Optional: Clean up old backups (keep last 5)
echo "🧹 Cleaning up old backups..."
ls -t /home/andrej/rainlo-backup-* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

echo "✅ All done!"
