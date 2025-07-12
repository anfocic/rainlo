# Rainlo Deployment on Render

## Why Render?
- ✅ **Zero server management** - No SSH, no server maintenance
- ✅ **Automatic deployments** - Push to GitHub, auto-deploy
- ✅ **Free tier available** - Perfect for development and small projects
- ✅ **Built-in database** - PostgreSQL included
- ✅ **SSL certificates** - Automatic HTTPS
- ✅ **Easy scaling** - Upgrade plans as needed

## Prerequisites
- GitHub repository with your Laravel code
- Render account (free at render.com)

## Deployment Steps

### 1. Push Your Code to GitHub
```bash
git add .
git commit -m "Prepare for Render deployment"
git push origin master
```

### 2. Create Render Account
1. Go to [render.com](https://render.com)
2. Sign up with your GitHub account
3. Authorize Render to access your repositories

### 3. Deploy Using render.yaml (Recommended)

#### Option A: Automatic Deployment with render.yaml
1. **Connect Repository**:
   - Click "New +" → "Blueprint"
   - Connect your GitHub repository
   - Render will automatically detect the `render.yaml` file

2. **Review Configuration**:
   - Service name: `rainlo-api`
   - Database: `rainlo-db` (PostgreSQL)
   - Plan: Starter (Free)

3. **Deploy**:
   - Click "Apply"
   - Render will create both web service and database
   - Wait for deployment to complete (5-10 minutes)

#### Option B: Manual Setup
If you prefer manual setup:

1. **Create Database First**:
   - Click "New +" → "PostgreSQL"
   - Name: `rainlo-db`
   - Plan: Starter (Free for 90 days)
   - Region: Oregon (or closest to you)

2. **Create Web Service**:
   - Click "New +" → "Web Service"
   - Connect your GitHub repository
   - Configure:
     - Name: `rainlo-api`
     - Runtime: Docker
     - Plan: Starter (Free)
     - Branch: `master`

3. **Set Environment Variables**:
   Copy from `.env.render` file:
   ```
   APP_NAME=Rainlo API
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:2sz808BitecfFDChCP410Yu4nCOb62tnUDPzBPEyjSc=
   APP_URL=https://rainlo-api.onrender.com
   DB_CONNECTION=pgsql
   CACHE_STORE=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=database
   LOG_CHANNEL=stderr
   LOG_LEVEL=info
   BCRYPT_ROUNDS=12
   ```

   Database variables (get from your database service):
   ```
   DB_HOST=[from database service]
   DB_PORT=[from database service]
   DB_DATABASE=[from database service]
   DB_USERNAME=[from database service]
   DB_PASSWORD=[from database service]
   ```

### 4. Update Your Domain (Optional)
1. **Custom Domain**:
   - In your web service settings
   - Add custom domain: `api.rainlo.app`
   - Update DNS records as instructed

2. **Update APP_URL**:
   - Change `APP_URL` environment variable to your custom domain
   - Or keep the Render URL: `https://rainlo-api.onrender.com`

## What Happens During Deployment

1. **Build Process**:
   - Render pulls your code from GitHub
   - Builds Docker container using your Dockerfile
   - Installs PHP dependencies with Composer
   - Caches Laravel configurations

2. **Database Setup**:
   - PostgreSQL database is created
   - Environment variables are automatically configured
   - Database migrations run automatically

3. **Service Start**:
   - PHP-FPM and Nginx start via Supervisor
   - Application becomes available at your Render URL

## Automatic Deployments

Once set up, every push to your `master` branch will:
1. Trigger automatic deployment on Render
2. Build new Docker container
3. Run database migrations
4. Deploy new version with zero downtime

## Monitoring and Logs

### View Logs
- Go to your service dashboard on Render
- Click "Logs" tab
- Real-time logs from your application

### Useful Commands
Access your application container:
```bash
# Not needed with Render - use the web dashboard instead
```

## Troubleshooting

### Common Issues

1. **Build Fails**:
   - Check build logs in Render dashboard
   - Ensure Dockerfile is correct
   - Verify composer.json dependencies

2. **Database Connection Issues**:
   - Verify database service is running
   - Check environment variables are set correctly
   - Ensure PostgreSQL extensions are installed

3. **Migration Fails**:
   - Check database logs
   - Verify migration files syntax
   - Ensure database user has proper permissions

### Environment Variables
If you need to update environment variables:
1. Go to your web service dashboard
2. Click "Environment" tab
3. Add/edit variables
4. Service will automatically redeploy

## Scaling and Pricing

### Free Tier Limits
- **Web Service**: 750 hours/month (enough for 24/7 if only service)
- **Database**: Free for 90 days, then $7/month
- **Bandwidth**: 100GB/month
- **Build time**: 500 minutes/month

### Upgrading
When you need more resources:
- **Starter Plan**: $7/month (more CPU/RAM)
- **Standard Plan**: $25/month (dedicated resources)
- **Pro Plan**: $85/month (high performance)

## Backup and Recovery

### Database Backups
- Render automatically backs up PostgreSQL databases
- Point-in-time recovery available on paid plans
- Manual backups can be created from dashboard

### Code Rollbacks
- Easy rollback to previous deployments
- Git-based deployment means easy version control
- Can redeploy any previous commit

## Security

### Automatic Features
- ✅ SSL certificates (automatic HTTPS)
- ✅ DDoS protection
- ✅ Private networking between services
- ✅ Environment variable encryption

### Best Practices
- Use strong `APP_KEY` (Laravel will generate)
- Keep database credentials secure (auto-managed)
- Use environment variables for all secrets
- Enable 2FA on your Render account

## Next Steps

After successful deployment:

1. **Test your API endpoints**
2. **Set up monitoring** (Render provides basic metrics)
3. **Configure custom domain** if needed
4. **Set up frontend** to connect to your API
5. **Monitor usage** and upgrade plan if needed

Your API will be available at:
- Render URL: `https://rainlo-api.onrender.com`
- Custom domain: `https://api.rainlo.app` (if configured)

## Support

- **Render Documentation**: [render.com/docs](https://render.com/docs)
- **Laravel Documentation**: [laravel.com/docs](https://laravel.com/docs)
- **Community**: Render Discord, Laravel Forums
