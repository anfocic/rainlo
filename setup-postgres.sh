#!/bin/bash

# Rainlo PostgreSQL Setup Script
# This script helps set up the PostgreSQL environment for Rainlo

set -e

echo "🐘 Setting up Rainlo with PostgreSQL..."

# Check if .env file exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env file and set your database password and APP_KEY"
    echo "   You can generate an APP_KEY with: php artisan key:generate"
fi

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

echo "🔧 Building Docker containers..."
docker-compose build

echo "🚀 Starting PostgreSQL database..."
docker-compose up -d db

echo "⏳ Waiting for PostgreSQL to be ready..."
sleep 10

# Check if database is ready
until docker-compose exec -T db pg_isready -U rainlo -d rainlo; do
    echo "Waiting for PostgreSQL..."
    sleep 2
done

echo "✅ PostgreSQL is ready!"

echo "🔄 Running database migrations..."
docker-compose run --rm app php artisan migrate

echo "🌱 Running database seeders (if any)..."
docker-compose run --rm app php artisan db:seed --force || echo "No seeders found, skipping..."

echo "🎉 Setup complete!"
echo ""
echo "To start the application:"
echo "  docker-compose up"
echo ""
echo "To access the application:"
echo "  http://localhost:8080"
echo ""
echo "To access PostgreSQL directly:"
echo "  docker-compose exec db psql -U rainlo -d rainlo"
echo ""
echo "To view logs:"
echo "  docker-compose logs -f"
