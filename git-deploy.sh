#!/bin/bash

# Refined Git-based deployment script for Rainlo
# Usage: ./deploy.sh [--skip-backup] [--skip-deps] [--branch=branch-name]

set -e

### CONFIGURATION ###
PROJECT_PATH="/opt/rainlo"
LOGFILE="/var/log/rainlo-deploy.log"
DEFAULT_BRANCH="master"
APP_CONTAINER="app"
DB_CONTAINER="db"
#####################

### Parse CLI Arguments ###
SKIP_BACKUP=false
SKIP_DEPS=false
BRANCH="$DEFAULT_BRANCH"

for arg in "$@"; do
  case $arg in
    --skip-backup) SKIP_BACKUP=true ;;
    --skip-deps) SKIP_DEPS=true ;;
    --branch=*) BRANCH="${arg#*=}" ;;
  esac
done

### Logging ###
mkdir -p "$(dirname "$LOGFILE")"
exec > >(tee -a "$LOGFILE") 2>&1

echo "🚀 Starting deployment [branch: $BRANCH]..."

### Clone Repo if Needed ###
if [ ! -d "$PROJECT_PATH/.git" ]; then
  echo "❌ Project directory not found. Cloning repository..."
  git clone https://github.com/anfocic/rainlo.git "$PROJECT_PATH"
fi

cd "$PROJECT_PATH"

### Backup ###
if [ "$SKIP_BACKUP" = false ]; then
  BACKUP_PATH="/opt/rainlo-backup-$(date +%Y%m%d-%H%M%S)"
  echo "📦 Creating backup at $BACKUP_PATH..."
  rsync -a --exclude={node_modules,vendor,.git} "$PROJECT_PATH/" "$BACKUP_PATH/"
  echo "✅ Backup created."
fi

### Git Pull ###
echo "📥 Pulling latest changes from origin/$BRANCH..."
git fetch origin
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "⚠️  Local changes detected. Stashing them..."
  git stash push -m "Auto-stash before deployment $(date)"
fi
git reset --hard origin/"$BRANCH"

if git stash list | grep -q "Auto-stash before deployment"; then
  echo "📦 Restoring stashed changes..."
  git stash pop || echo "⚠️ Could not restore stashed changes automatically"
fi

### Dependencies ###
if [ "$SKIP_DEPS" = false ]; then
  if [ -f "composer.json" ]; then
    echo "📦 Installing PHP dependencies..."
    docker run --rm -v "$PWD":/app composer:latest install --no-dev --optimize-autoloader --working-dir=/app
  fi

  if [ -f "package.json" ]; then
    echo "📦 Installing Node dependencies..."
    docker run --rm -v "$PWD":/app -w /app node:18-alpine npm ci --production
  fi
else
  echo "⏩ Skipping dependency installation (--skip-deps)."
fi

### Environment Setup ###
if [ ! -f ".env" ] && [ -f ".env.production" ]; then
  cp .env.production .env
  echo "✅ Environment file created from .env.production"
fi

### Permissions ###
echo "🔧 Setting permissions..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R 1000:1000 storage bootstrap/cache 2>/dev/null || true

### Laravel Setup ###
if [ -f "artisan" ]; then
  if [ -f "docker-compose.yml" ]; then
    echo "🔧 Starting Docker containers..."
    docker-compose up -d --remove-orphans

    echo "⏳ Waiting for database to be ready..."
    timeout=60
    while [ $timeout -gt 0 ]; do
      if docker-compose exec -T "$DB_CONTAINER" mysqladmin ping -h localhost --silent; then
        echo "✅ Database is ready!"
        break
      fi
      echo "⏳ Waiting for DB... ($timeout seconds left)"
      sleep 2
      timeout=$((timeout - 2))
    done

    if [ $timeout -le 0 ]; then
      echo "❌ Database startup timeout reached."
      exit 1
    fi

    echo "⚙️ Running Laravel artisan commands..."
    docker-compose exec -T "$APP_CONTAINER" php artisan config:cache || true
    docker-compose exec -T "$APP_CONTAINER" php artisan route:cache || true
    docker-compose exec -T "$APP_CONTAINER" php artisan view:cache || true
    docker-compose exec -T "$APP_CONTAINER" php artisan migrate --force || true
  else
    echo "ℹ️ No docker-compose.yml found, skipping Laravel commands."
  fi
fi

### Clean Up Old Containers ###
echo "🧹 Cleaning up old containers..."
docker stop rainlo-phpmyadmin-1 rainlo-adminer-1 2>/dev/null || true
docker rm rainlo-phpmyadmin-1 rainlo-adminer-1 2>/dev/null || true

### Restart Services ###
if [ -f "docker-compose.yml" ]; then
  echo "🔄 Restarting containers..."
  docker-compose down --remove-orphans
  docker-compose up -d --remove-orphans
  docker-compose ps
elif systemctl is-active --quiet nginx 2>/dev/null; then
  echo "🔄 Reloading nginx..."
  sudo systemctl reload nginx
fi

### Cleanup Old Backups ###
if [ "$SKIP_BACKUP" = false ]; then
  echo "🧹 Cleaning up old backups (keeping last 5)..."
  ls -t /opt/rainlo-backup-* 2>/dev/null | tail -n +6 | xargs rm -rf 2>/dev/null || true
fi

echo "🎉 Deployment complete!"
