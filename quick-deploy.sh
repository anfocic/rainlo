#!/bin/bash

# Quick deployment script - no rebuild, just restart
set -e

PROJECT_PATH="/opt/rainlo"

echo "🚀 Quick deployment (no rebuild)..."

cd "$PROJECT_PATH"

# Pull latest changes
echo "📥 Pulling latest changes..."
git fetch origin
git reset --hard origin/master

# Just restart containers (no rebuild)
echo "🔄 Restarting containers..."
docker-compose restart

# Run Laravel commands
echo "🔧 Running Laravel commands..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan migrate --force

echo "✅ Quick deployment completed!"
