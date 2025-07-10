# SmartTax Deployment Guide

This guide will help you set up automated CI/CD deployment from GitHub to your home server using GitHub Actions and Docker.

## 🏗️ Architecture Overview

```
GitHub Repository → GitHub Actions → GitHub Container Registry → Home Server
     ↓                    ↓                      ↓                    ↓
  Code Push         Build & Test            Store Image         Deploy & Run
```

## 🚀 Quick Start

### 1. Server Setup

Run this on your home server (Ubuntu/Debian):

```bash
# Download and run the server setup script
curl -fsSL https://raw.githubusercontent.com/anfocic/smartax/main/setup-server.sh | sudo bash
```

Or manually:

```bash
# Copy setup-server.sh to your server
scp setup-server.sh user@your-server:/tmp/
ssh user@your-server
sudo bash /tmp/setup-server.sh
```

### 2. Copy Deployment Files

Copy these files to your server at `/opt/smartax/`:

```bash
scp docker-compose.prod.yml user@your-server:/opt/smartax/
scp deploy-server.sh user@your-server:/opt/smartax/
sudo chmod +x /opt/smartax/deploy-server.sh
```

### 3. Configure GitHub Secrets

Add these secrets to your GitHub repository (Settings → Secrets and variables → Actions):

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `HOST` | Your server's IP address | `192.168.1.100` |
| `USERNAME` | SSH username | `smartax` |
| `SSH_KEY` | Private SSH key | Contents of `/home/smartax/.ssh/id_rsa` |
| `PORT` | SSH port | `22` |

### 4. Deploy!

Push to the `main` branch and watch the magic happen! 🎉

```bash
git add .
git commit -m "Setup CI/CD deployment"
git push origin main
```

## 📁 File Structure

```
/opt/smartax/
├── docker-compose.prod.yml    # Production Docker Compose
├── deploy-server.sh           # Deployment script
├── .env.production           # Environment variables (auto-generated)
├── backups/                  # Database backups
├── logs/                     # Application logs
├── nginx/                    # Nginx configuration
└── mysql-init/              # MySQL initialization scripts
```

## 🔧 Configuration

### Environment Variables

The deployment automatically generates secure environment variables:

- `APP_KEY`: Laravel application key
- `DB_PASSWORD`: Database password
- `MYSQL_ROOT_PASSWORD`: MySQL root password
- `APP_URL`: Application URL

You can customize these by editing `/opt/smartax/.env.production` on your server.

### Docker Compose Services

The production setup includes:

- **app**: Your Laravel application
- **db**: MySQL 8.0 database
- **redis**: Redis for caching (optional)
- **nginx**: Reverse proxy (optional)

## 🔄 Deployment Process

1. **Test**: Runs PHPUnit tests with MySQL
2. **Build**: Creates Docker image and pushes to GitHub Container Registry
3. **Deploy**: 
   - Backs up current database
   - Pulls new image
   - Stops old containers
   - Starts new containers
   - Runs migrations
   - Performs health check

## 🛠️ Manual Operations

### Manual Deployment

```bash
sudo -u smartax /opt/smartax/deploy-server.sh
```

### View Logs

```bash
# Application logs
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml logs -f app

# All services
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml logs -f

# System service logs
journalctl -u smartax -f
```

### Database Operations

```bash
# Access database
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml exec db mysql -u root -p smartax

# Create manual backup
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml exec db mysqldump -u root -p smartax > backup.sql

# Restore from backup
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml exec -T db mysql -u root -p smartax < backup.sql
```

### Container Management

```bash
# Check status
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml ps

# Restart services
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml restart

# Update to latest image
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml pull
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml up -d
```

## 🔒 Security Features

- **Firewall**: UFW configured with minimal open ports
- **Fail2ban**: Protection against brute force attacks
- **User isolation**: Dedicated `smartax` user for deployments
- **Secure secrets**: Auto-generated passwords and keys
- **Container isolation**: Docker network isolation

## 🚨 Troubleshooting

### Deployment Fails

1. Check GitHub Actions logs
2. Verify server connectivity: `ssh smartax@your-server`
3. Check server logs: `journalctl -u smartax -f`
4. Verify Docker status: `sudo systemctl status docker`

### Application Not Accessible

1. Check container status: `docker-compose ps`
2. Check application logs: `docker-compose logs app`
3. Verify firewall: `sudo ufw status`
4. Test locally: `curl http://localhost:8080/up`

### Database Issues

1. Check database logs: `docker-compose logs db`
2. Verify database connectivity: `docker-compose exec app php artisan migrate:status`
3. Check disk space: `df -h`

## 📊 Monitoring

### Health Checks

The deployment includes automatic health checks:

- Application health: `http://your-server:8080/up`
- Database connectivity: Verified during deployment
- Container status: Monitored by systemd

### Log Rotation

Logs are automatically rotated daily and kept for 30 days.

## 🔄 Updates and Maintenance

### Updating the Application

Simply push to the `main` branch - GitHub Actions will handle the rest!

### Updating the Server

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update Docker
sudo apt update && sudo apt install docker-ce docker-ce-cli containerd.io

# Restart services if needed
sudo systemctl restart smartax
```

## 🎯 Next Steps

1. **SSL/HTTPS**: Configure Let's Encrypt for HTTPS
2. **Domain**: Point your domain to your server
3. **Monitoring**: Set up monitoring with Prometheus/Grafana
4. **Backups**: Configure automated off-site backups
5. **Scaling**: Add load balancing for multiple instances

## 📞 Support

If you encounter issues:

1. Check the logs first
2. Review this documentation
3. Check GitHub Actions workflow logs
4. Verify server configuration

Happy deploying! 🚀
