#!/bin/bash

# Simple Docker-based deployment script
set -e

PROJECT_PATH="/home/andrej/rainlo"

echo "🚀 Starting simple deployment..."

# Navigate to project
cd "$PROJECT_PATH"

# Pull latest changes
echo "📥 Pulling latest changes..."
git fetch origin
git reset --hard origin/master

# Set up environment
echo "⚙️ Setting up environment..."
if [ ! -f ".env" ] && [ -f ".env.production" ]; then
    cp .env.production .env
    echo "✅ Environment file created"
fi

# Restart Docker containers (this will rebuild and install dependencies)
echo "🔄 Restarting Docker containers..."
docker-compose down
docker-compose up -d --build

# Wait for containers to be ready
echo "⏳ Waiting for containers to start..."
sleep 10

# Run Laravel commands if containers are running
echo "🔧 Running Laravel commands..."
docker-compose exec -T app php artisan migrate --force || echo "Migration failed"
docker-compose exec -T app php artisan config:cache || echo "Config cache failed"

# Show status
echo "📊 Container status:"
docker-compose ps

echo "🎉 Deployment completed!"
echo "🌐 Your application should be running on the configured ports"
