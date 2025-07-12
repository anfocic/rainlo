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

# Install/update dependencies
echo "📦 Installing dependencies..."
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

if [ -f "package.json" ]; then
    npm ci --production
fi

# Set up environment
echo "⚙️ Setting up environment..."
if [ ! -f ".env" ] && [ -f ".env.production" ]; then
    cp .env.production .env
    echo "✅ Environment file created from .env.production"
fi

# Laravel specific commands
if [ -f "artisan" ]; then
    echo "🔧 Running Laravel commands..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan migrate --force

    # Set proper permissions
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Restart services
echo "🔄 Restarting services..."
if [ -f "docker-compose.yml" ]; then
    docker-compose down
    docker-compose up -d --build
elif systemctl is-active --quiet nginx; then
    sudo systemctl reload nginx
fi

echo "🎉 Deployment completed successfully!"
echo "📍 Backup location: $BACKUP_PATH"
echo "🌐 Your application should now be live!"

# Optional: Clean up old backups (keep last 5)
echo "🧹 Cleaning up old backups..."
ls -t /home/andrej/rainlo-backup-* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true

echo "✅ All done!"
