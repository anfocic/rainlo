#!/bin/bash

# SmartTax Home Server Setup Script
# Run this script on your home server to prepare it for deployments

set -e

echo "🏠 Setting up SmartTax deployment environment on home server..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    error "Please run this script as root (use sudo)"
fi

# Update system packages
log "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
log "Installing required packages..."
apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    ufw \
    fail2ban

# Install Docker
log "Installing Docker..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    apt update
    apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    systemctl enable docker
    systemctl start docker
    success "Docker installed successfully"
else
    success "Docker is already installed"
fi

# Install Docker Compose (standalone)
log "Installing Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    success "Docker Compose installed successfully"
else
    success "Docker Compose is already installed"
fi

# Create deployment user
log "Creating deployment user..."
if ! id "smartax" &>/dev/null; then
    useradd -m -s /bin/bash smartax
    usermod -aG docker smartax
    success "User 'smartax' created and added to docker group"
else
    success "User 'smartax' already exists"
fi

# Create deployment directory
log "Creating deployment directory..."
mkdir -p /opt/smartax
chown smartax:smartax /opt/smartax
chmod 755 /opt/smartax

# Create subdirectories
mkdir -p /opt/smartax/{backups,logs,nginx,mysql-init}
chown -R smartax:smartax /opt/smartax

# Setup firewall
log "Configuring firewall..."
ufw --force enable
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 8080/tcp  # For direct access to the app
success "Firewall configured"

# Configure fail2ban
log "Configuring fail2ban..."
systemctl enable fail2ban
systemctl start fail2ban

# Create nginx configuration
log "Creating nginx configuration..."
cat > /opt/smartax/nginx/nginx.conf << 'EOF'
events {
    worker_connections 1024;
}

http {
    upstream app {
        server app:80;
    }

    server {
        listen 80;
        server_name _;

        location / {
            proxy_pass http://app;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
}
EOF

# Create systemd service for auto-restart
log "Creating systemd service..."
cat > /etc/systemd/system/smartax.service << 'EOF'
[Unit]
Description=SmartTax Application
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/opt/smartax
ExecStart=/usr/local/bin/docker-compose -f docker-compose.prod.yml up -d
ExecStop=/usr/local/bin/docker-compose -f docker-compose.prod.yml down
TimeoutStartSec=0
User=smartax
Group=smartax

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable smartax.service

# Create log rotation
log "Setting up log rotation..."
cat > /etc/logrotate.d/smartax << 'EOF'
/opt/smartax/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 smartax smartax
}
EOF

# Create deployment webhook script (optional)
log "Creating deployment webhook..."
cat > /opt/smartax/webhook.sh << 'EOF'
#!/bin/bash
# Simple webhook receiver for deployments
# You can call this from GitHub Actions or manually

cd /opt/smartax
./deploy-server.sh
EOF

chmod +x /opt/smartax/webhook.sh
chown smartax:smartax /opt/smartax/webhook.sh

# Generate SSH key for GitHub Actions (if needed)
log "Setting up SSH access..."
if [ ! -f /home/smartax/.ssh/id_rsa ]; then
    sudo -u smartax ssh-keygen -t rsa -b 4096 -f /home/smartax/.ssh/id_rsa -N ""
    success "SSH key generated for smartax user"
    echo ""
    echo "🔑 Add this public key to your GitHub repository secrets as SSH_KEY:"
    echo "----------------------------------------"
    cat /home/smartax/.ssh/id_rsa
    echo "----------------------------------------"
    echo ""
    echo "📝 Also add these to your GitHub repository secrets:"
    echo "HOST: $(curl -s ifconfig.me || hostname -I | awk '{print $1}')"
    echo "USERNAME: smartax"
    echo "PORT: 22"
    echo ""
fi

success "🎉 Server setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Copy docker-compose.prod.yml to /opt/smartax/"
echo "2. Copy deploy-server.sh to /opt/smartax/ and make it executable"
echo "3. Add the SSH public key to your GitHub repository secrets"
echo "4. Configure your GitHub repository secrets with server details"
echo "5. Push to main branch to trigger your first deployment!"
echo ""
echo "🔧 Useful commands:"
echo "• Check service status: systemctl status smartax"
echo "• View logs: journalctl -u smartax -f"
echo "• Manual deployment: sudo -u smartax /opt/smartax/deploy-server.sh"
echo "• Check containers: sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml ps"
