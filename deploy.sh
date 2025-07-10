#!/bin/bash

# Simple deployment script for SmartTax API
echo "🚀 Deploying SmartTax API..."

# Copy environment file for Docker
cp .env.docker .env

# Load environment variables
export $(cat .env.docker | xargs)

# Build and start containers
echo "📦 Building containers..."
docker-compose build

echo "🔄 Starting services..."
docker-compose up -d

# Wait for database
echo "⏳ Waiting for database..."
sleep 15

# Run migrations
echo "🗄️  Running migrations..."
docker-compose exec app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker-compose exec app php artisan db:seed --force

echo "✅ Deployment complete!"
echo "🌐 API available at: http://localhost:8080"
echo "📊 Test with: curl http://localhost:8080/up"
