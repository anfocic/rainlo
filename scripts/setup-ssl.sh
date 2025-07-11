#!/bin/bash

# SSL Setup Script for api.rainlo.app
# This script sets up Let's Encrypt SSL certificates

set -e

echo "🔐 Setting up SSL certificates for api.rainlo.app..."

# Check if domain is provided
DOMAIN=${1:-api.rainlo.app}
EMAIL=${2:-your-email@example.com}

echo "Domain: $DOMAIN"
echo "Email: $EMAIL"

# Create nginx directory if it doesn't exist
mkdir -p nginx/ssl

# First, start nginx without SSL to handle ACME challenge
echo "📝 Creating temporary nginx config for ACME challenge..."
cat > nginx/nginx-temp.conf << EOF
events {
    worker_connections 1024;
}

http {
    server {
        listen 80;
        server_name $DOMAIN;
        
        location /.well-known/acme-challenge/ {
            root /var/www/certbot;
        }
        
        location / {
            return 200 'OK';
            add_header Content-Type text/plain;
        }
    }
}
EOF

# Start temporary nginx
echo "🚀 Starting temporary nginx for ACME challenge..."
docker run -d --name nginx-temp \
    -p 80:80 \
    -v $(pwd)/nginx/nginx-temp.conf:/etc/nginx/nginx.conf:ro \
    -v certbot_www:/var/www/certbot \
    nginx:alpine

# Wait a moment for nginx to start
sleep 5

# Get SSL certificate
echo "🔒 Requesting SSL certificate from Let's Encrypt..."
docker run --rm \
    -v $(pwd)/nginx/ssl:/etc/letsencrypt \
    -v certbot_www:/var/www/certbot \
    certbot/certbot certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email $EMAIL \
    --agree-tos \
    --no-eff-email \
    -d $DOMAIN

# Stop temporary nginx
echo "🛑 Stopping temporary nginx..."
docker stop nginx-temp
docker rm nginx-temp

# Copy certificates to the correct location
echo "📋 Setting up certificate files..."
docker run --rm \
    -v $(pwd)/nginx/ssl:/etc/letsencrypt \
    -v $(pwd)/nginx/ssl:/output \
    alpine:latest sh -c "
        cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem /output/fullchain.pem
        cp /etc/letsencrypt/live/$DOMAIN/privkey.pem /output/privkey.pem
        chmod 644 /output/fullchain.pem
        chmod 600 /output/privkey.pem
    "

# Clean up temporary config
rm nginx/nginx-temp.conf

echo "✅ SSL certificates have been set up successfully!"
echo "📁 Certificates are located in nginx/ssl/"
echo "🚀 You can now start your production environment with: docker-compose -f docker-compose.prod.yml up -d"
