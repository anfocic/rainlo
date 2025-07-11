# DNS Setup for api.rainlo.app

## Overview
This guide helps you configure DNS for your Rainlo API backend at `api.rainlo.app`.

## DNS Configuration

### 1. Add A Record for API Subdomain

In your domain registrar's DNS settings (where you manage rainlo.app), add:

```
Type: A
Name: api
Value: YOUR_SERVER_IP_ADDRESS
TTL: 300 (or default)
```

This creates `api.rainlo.app` pointing to your server.

### 2. Verify DNS Propagation

Check if DNS is working:
```bash
# Check A record
dig api.rainlo.app

# Check from different locations
nslookup api.rainlo.app 8.8.8.8
```

### 3. Test HTTP Access (before SSL)

Once DNS propagates, test basic connectivity:
```bash
curl -I http://api.rainlo.app
```

## SSL Certificate Setup

### 1. Run SSL Setup Script

After DNS is working, set up SSL certificates:
```bash
./scripts/setup-ssl.sh your-email@example.com
```

### 2. Deploy Production Environment

```bash
./scripts/deploy-production.sh
```

## Frontend Configuration

Update your frontend (rainlo.app) to use the new API URL:

### Environment Variables
```env
# For production
NEXT_PUBLIC_API_URL=https://api.rainlo.app

# For development (if testing locally)
NEXT_PUBLIC_API_URL=http://localhost:8080
```

### API Client Configuration
```javascript
// In your frontend API client
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'https://api.rainlo.app';

// Example fetch
const response = await fetch(`${API_BASE_URL}/api/login`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  credentials: 'include', // Important for CORS with credentials
  body: JSON.stringify({ email, password })
});
```

## Verification Steps

### 1. Test API Health
```bash
curl https://api.rainlo.app/api/health
```

Expected response:
```json
{
  "status": "healthy",
  "timestamp": "2025-07-11T...",
  "app": "Rainlo API",
  "version": "1.0.0",
  "services": {
    "database": "connected",
    "application": "running"
  }
}
```

### 2. Test CORS from Frontend
```javascript
// Test from your frontend domain (rainlo.app)
fetch('https://api.rainlo.app/api/health')
  .then(response => response.json())
  .then(data => console.log('API Health:', data))
  .catch(error => console.error('CORS Error:', error));
```

### 3. Test Authentication Flow
```bash
# Register a test user
curl -X POST https://api.rainlo.app/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password","password_confirmation":"password"}'

# Login
curl -X POST https://api.rainlo.app/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

## Troubleshooting

### DNS Issues
- Wait 5-15 minutes for DNS propagation
- Check with multiple DNS servers: `8.8.8.8`, `1.1.1.1`
- Verify A record points to correct IP

### SSL Issues
- Ensure port 80 is open for ACME challenge
- Check nginx logs: `docker-compose -f docker-compose.prod.yml logs nginx`
- Verify domain ownership

### CORS Issues
- Check browser developer tools for CORS errors
- Verify frontend domain is in allowed origins
- Ensure credentials are included in requests

### API Issues
- Check application logs: `docker-compose -f docker-compose.prod.yml logs app`
- Verify database connection
- Test health endpoint

## Security Considerations

1. **Firewall**: Only expose ports 80, 443, and 22 (SSH)
2. **SSL**: Always use HTTPS in production
3. **CORS**: Restrict to your frontend domain only
4. **Rate Limiting**: Nginx includes basic rate limiting
5. **Database**: Not exposed externally (only internal Docker network)

## Monitoring

### Log Monitoring
```bash
# Follow all logs
docker-compose -f docker-compose.prod.yml logs -f

# Follow specific service
docker-compose -f docker-compose.prod.yml logs -f nginx
docker-compose -f docker-compose.prod.yml logs -f app
```

### Health Monitoring
Set up monitoring to check `https://api.rainlo.app/api/health` regularly.
