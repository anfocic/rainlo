#!/bin/bash

# 🛠️ Rainlo API Server Setup Script
# Run this script on your server to set up the deployment environment

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

echo ""
log_info "🛠️  Setting up Rainlo API deployment environment"
echo "================================================"

# Create project directory
log_info "Creating project directory..."
sudo mkdir -p /opt/rainlo
sudo chown $USER:$USER /opt/rainlo
cd /opt/rainlo

# Create .env.production file
log_info "Creating .env.production file..."
cat > .env.production << 'EOF'
# Production Environment for api.rainlo.app
APP_NAME="Rainlo API"
APP_ENV=production
APP_KEY=base64:2sz808BitecfFDChCP410Yu4nCOb62tnUDPzBPEyjSc=
APP_DEBUG=false
APP_URL=https://api.rainlo.app

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=rainlo
DB_USERNAME=rainlo
DB_PASSWORD=rainlo_secure_2025

# Cache and Session (using file-based instead of Redis)
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=stderr
LOG_LEVEL=info

# Mail Configuration (optional)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@rainlo.app
MAIL_FROM_NAME="Rainlo"

# Security
BCRYPT_ROUNDS=12
EOF

# Create docker-compose.prod.yml file
log_info "Creating docker-compose.prod.yml file..."
cat > docker-compose.prod.yml << 'EOF'
version: '3.8'

services:
  app:
    image: fole/rainlo-api:latest
    container_name: rainlo-app
    restart: unless-stopped
    ports:
      - "8000:80"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    env_file:
      - .env.production
    networks:
      - rainlo-network
    depends_on:
      - db
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

  db:
    image: postgres:15
    container_name: rainlo-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - rainlo-db-data:/var/lib/postgresql/data
    networks:
      - rainlo-network
    ports:
      - "5432:5432"
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"

networks:
  rainlo-network:
    driver: bridge

volumes:
  rainlo-db-data:
EOF

# Create deployment script
log_info "Creating deployment script..."
# Note: In a real scenario, you'd copy the deploy.sh content here
# For now, we'll create a placeholder
cat > deploy.sh << 'EOF'
#!/bin/bash
# Deployment script will be provided separately
echo "Please copy the deploy.sh script content from your local machine"
EOF

chmod +x deploy.sh

# Show final status
echo ""
log_success "🎉 Server setup completed!"
echo "=========================="
echo ""
log_info "Files created in /opt/rainlo/:"
echo "✅ .env.production"
echo "✅ docker-compose.prod.yml" 
echo "⚠️  deploy.sh (needs content)"
echo ""
log_warning "Next steps:"
echo "1. Copy the deploy.sh script content from your local machine"
echo "2. Make sure deploy.sh is executable: chmod +x deploy.sh"
echo "3. Run the deployment: ./deploy.sh"
echo ""
log_info "Current directory: $(pwd)"
ls -la
