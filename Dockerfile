FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    postgresql-client \
    nginx \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www/

# Remove Scribe config for production (before composer install)
RUN rm -f config/scribe.php

# Create a basic .env file for production
RUN echo "APP_NAME=\"Rainlo API\"" > /var/www/.env \
    && echo "APP_ENV=production" >> /var/www/.env \
    && echo "APP_KEY=base64:2sz808BitecfFDChCP410Yu4nCOb62tnUDPzBPEyjSc=" >> /var/www/.env \
    && echo "APP_DEBUG=false" >> /var/www/.env \
    && echo "APP_URL=http://localhost" >> /var/www/.env \
    && echo "DB_CONNECTION=pgsql" >> /var/www/.env \
    && echo "DB_HOST=db" >> /var/www/.env \
    && echo "DB_PORT=5432" >> /var/www/.env \
    && echo "DB_DATABASE=rainlo" >> /var/www/.env \
    && echo "DB_USERNAME=rainlo" >> /var/www/.env \
    && echo "DB_PASSWORD=password" >> /var/www/.env \
    && echo "CACHE_STORE=file" >> /var/www/.env \
    && echo "SESSION_DRIVER=file" >> /var/www/.env \
    && echo "QUEUE_CONNECTION=sync" >> /var/www/.env \
    && echo "LOG_CHANNEL=stderr" >> /var/www/.env \
    && echo "LOG_LEVEL=error" >> /var/www/.env

# Install dependencies without dev dependencies
RUN composer install --optimize-autoloader --no-dev

# Clear any cached configuration to ensure fresh config
RUN rm -rf /var/www/bootstrap/cache/config.php \
    && rm -rf /var/www/bootstrap/cache/routes*.php \
    && rm -rf /var/www/bootstrap/cache/services.php

# Create necessary directories and set permissions
RUN mkdir -p /var/www/storage/logs \
    && mkdir -p /var/www/storage/framework/cache \
    && mkdir -p /var/www/storage/framework/sessions \
    && mkdir -p /var/www/storage/framework/views \
    && mkdir -p /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Copy PHP configuration
COPY php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy custom entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 80
EXPOSE 80

# Use custom entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
