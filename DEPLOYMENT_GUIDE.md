# 🚀 Rainlo API Production Deployment Guide

This guide shows how to deploy your Rainlo API to production using Docker Hub images (no source code on server).

## 📋 Prerequisites

- Docker and Docker Compose installed on server
- Domain configured (api.rainlo.app)
- Server access via SSH

## 🗂️ Server File Structure

Your production server only needs these files:

```
/opt/rainlo/
├── docker-compose.prod.yml    # Docker configuration
├── .env.production           # Environment variables
└── deploy.sh                # Deployment script (executable)
```

**No source code needed on server!** 🎉

## 🔧 Step 1: Initial Server Setup

### 1.1 Create Project Directory
```bash
sudo mkdir -p /opt/rainlo
sudo chown $USER:$USER /opt/rainlo
cd /opt/rainlo
```

### 1.2 Create Environment File
Create `.env.production` with your production settings:

```bash
nano .env.production
```

Copy the contents from your local `.env.production` file.

### 1.3 Create Docker Compose File
Create `docker-compose.prod.yml`:

```bash
nano docker-compose.prod.yml
```

Copy the contents from your local `docker-compose.prod.yml` file.

### 1.4 Create Deployment Script
Create the deployment script:

```bash
nano deploy.sh
chmod +x deploy.sh
```

Copy the deployment script content (provided separately).

## 🚀 Step 2: Initial Deployment

Run the deployment script:

```bash
./deploy.sh
```

This will:
- Pull the latest Docker image from Docker Hub
- Start PostgreSQL database
- Run Laravel migrations
- Seed the database with test data
- Start the API server

## 🔄 Step 3: Updating Your Application

When you have new code changes:

1. **On your local machine:**
   ```bash
   # Build and push new image
   docker-compose build app
   docker tag rainlo-app:latest fole/rainlo-api:latest
   docker push fole/rainlo-api:latest
   ```

2. **On your server:**
   ```bash
   # Deploy the update
   ./deploy.sh
   ```

## 🔍 Step 4: Monitoring and Management

### Check Application Status
```bash
docker-compose -f docker-compose.prod.yml ps
```

### View Logs
```bash
# API logs
docker-compose -f docker-compose.prod.yml logs -f app

# Database logs
docker-compose -f docker-compose.prod.yml logs -f db
```

### Access Database
```bash
docker-compose -f docker-compose.prod.yml exec db psql -U rainlo -d rainlo
```

### Stop Application
```bash
docker-compose -f docker-compose.prod.yml down
```

### Restart Application
```bash
docker-compose -f docker-compose.prod.yml restart
```

## 🛠️ Troubleshooting

### Issue: Container won't start
```bash
# Check logs
docker-compose -f docker-compose.prod.yml logs app

# Check if ports are available
sudo netstat -tulpn | grep :8000
```

### Issue: Database connection failed
```bash
# Check database status
docker-compose -f docker-compose.prod.yml exec db pg_isready -U rainlo

# Reset database (⚠️ destroys data)
docker-compose -f docker-compose.prod.yml down -v
./deploy.sh
```

### Issue: Image pull failed
```bash
# Manually pull image
docker pull fole/rainlo-api:latest

# Check Docker Hub connectivity
curl -I https://registry-1.docker.io/
```

## 🔐 Security Notes

- Ensure `.env.production` has secure passwords
- Keep Docker and system updated
- Monitor logs for suspicious activity
- Consider setting up SSL/TLS termination

## 📊 Health Checks

Your API includes health check endpoints:

- **Health Check**: `GET https://api.rainlo.app/api/health`
- **Database Check**: `GET https://api.rainlo.app/api/health/database`

## 🎯 Production URLs

- **API Base**: `https://api.rainlo.app/api/`
- **Health Check**: `https://api.rainlo.app/api/health`
- **Documentation**: `https://api.rainlo.app/api/docs` (if enabled)

---

## 🆘 Quick Commands Reference

```bash
# Deploy/Update
./deploy.sh

# Check status
docker-compose -f docker-compose.prod.yml ps

# View logs
docker-compose -f docker-compose.prod.yml logs -f app

# Restart
docker-compose -f docker-compose.prod.yml restart

# Stop
docker-compose -f docker-compose.prod.yml down

# Database access
docker-compose -f docker-compose.prod.yml exec db psql -U rainlo -d rainlo
```

---

**🎉 Your Rainlo API is now running in production with zero source code on the server!**
