#!/bin/bash

# Server Setup Script for Rainlo API
# Run this script on your server to prepare for CI/CD deployments

set -e

echo "🚀 Setting up server for Rainlo API CI/CD..."

# Create deployment directory
echo "📁 Creating deployment directory..."
sudo mkdir -p /opt/rainlo
sudo chown $USER:$USER /opt/rainlo

# Navigate to deployment directory
cd /opt/rainlo

# Clone the repository (you'll need to set up SSH keys)
echo "📥 Cloning repository..."
if [ ! -d ".git" ]; then
    git clone git@github.com:anfocic/smartax.git .
else
    echo "Repository already exists, pulling latest..."
    git pull origin master
fi

# Create production environment file
echo "📝 Setting up environment file..."
if [ ! -f ".env.production" ]; then
    cp .env.server.template .env.production
    echo "⚠️  Please edit .env.production and fill in the actual values:"
    echo "   - APP_KEY (generate with: php artisan key:generate --show)"
    echo "   - DB_PASSWORD (secure database password)"
    echo "   - MYSQL_ROOT_PASSWORD (secure root password)"
    echo "   - Mail settings (if needed)"
    echo ""
    echo "📝 Opening .env.production for editing..."
    nano .env.production
else
    echo "✅ .env.production already exists"
fi

# Make scripts executable
echo "🔧 Making scripts executable..."
chmod +x scripts/*.sh

# Create docker volumes
echo "🐳 Creating Docker volumes..."
docker volume create rainlo_db_data || true
docker volume create rainlo_app_storage || true
docker volume create rainlo_app_cache || true
docker volume create rainlo_nginx_logs || true

# Test Docker setup
echo "🧪 Testing Docker setup..."
docker-compose -f docker-compose.prod.yml config

echo "✅ Server setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Update your Cloudflare Tunnel configuration:"
echo "   sudo nano /etc/cloudflared/config.yml"
echo "   Add: - hostname: api.rainlo.app"
echo "        service: http://localhost:8080"
echo ""
echo "2. Restart Cloudflare Tunnel:"
echo "   sudo systemctl restart cloudflared"
echo ""
echo "3. Set up GitHub Secrets in your repository:"
echo "   - HOST: your server IP"
echo "   - USERNAME: $USER"
echo "   - SSH_KEY: your private SSH key"
echo "   - PORT: 22 (or your SSH port)"
echo "   - APP_KEY: (from .env.production)"
echo "   - DB_PASSWORD: (from .env.production)"
echo "   - MYSQL_ROOT_PASSWORD: (from .env.production)"
echo ""
echo "4. Push to master branch to trigger deployment!"
