#!/bin/bash

# Production Deployment Script for api.rainlo.app
# This script deploys the application to production with SSL

set -e

echo "🚀 Deploying Rainlo API to production..."

# Check if .env.production exists
if [ ! -f .env.production ]; then
    echo "❌ .env.production file not found!"
    echo "Please create .env.production with your production settings."
    exit 1
fi

# SSL is handled by Cloudflare Tunnel - no certificates needed

# Copy production environment
echo "📝 Setting up production environment..."
cp .env.production .env

# Stop existing containers
echo "🛑 Stopping existing containers..."
docker-compose -f docker-compose.prod.yml down || true

# Pull latest images
echo "📥 Pulling latest images..."
docker-compose -f docker-compose.prod.yml pull

# Build and start services
echo "🏗️ Building and starting services..."
docker-compose -f docker-compose.prod.yml up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 30

# Run migrations
echo "🗄️ Running database migrations..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# Clear and cache config
echo "🧹 Clearing and caching configuration..."
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:clear
docker-compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker-compose -f docker-compose.prod.yml exec -T app php artisan route:cache

# Check if services are running
echo "🔍 Checking service status..."
docker-compose -f docker-compose.prod.yml ps

# Test the API locally first
echo "🧪 Testing API endpoint locally..."
sleep 10
if curl -f -s http://localhost:8080/api/health > /dev/null; then
    echo "✅ API is responding locally!"
    echo "🌐 API should be available at: https://api.rainlo.app (via Cloudflare Tunnel)"
    echo "📝 Make sure to add api.rainlo.app to your Cloudflare Tunnel configuration"
else
    echo "⚠️ API health check failed. Check logs with: docker-compose -f docker-compose.prod.yml logs"
fi

echo "🎉 Deployment completed!"
echo "🌐 Your API is now available at: https://api.rainlo.app"
echo "📊 Monitor logs with: docker-compose -f docker-compose.prod.yml logs -f"
