#!/bin/bash

# Custom entrypoint for Rainlo API Docker container
# This replaces the Laravel Sail start-container script

set -e

echo "🚀 Starting Rainlo API..."

# Wait for database to be ready (optional)
if [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "⏳ Waiting for PostgreSQL to be ready..."
    until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" 2>/dev/null; do
        echo "PostgreSQL is unavailable - sleeping"
        sleep 2
    done
    echo "✅ PostgreSQL is ready!"
fi

# Clear any cached configuration
echo "🧹 Clearing cached configuration..."
php artisan config:clear --quiet || true
php artisan route:clear --quiet || true
php artisan view:clear --quiet || true

# Cache configuration for production
echo "⚡ Caching configuration..."
php artisan config:cache --quiet
php artisan route:cache --quiet
php artisan view:cache --quiet

echo "✅ Rainlo API is ready!"

# Start supervisor to manage nginx and php-fpm
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
