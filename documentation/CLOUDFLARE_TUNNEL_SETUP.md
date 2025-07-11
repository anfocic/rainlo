# Cloudflare Tunnel Configuration for api.rainlo.app

## Step 1: Update Tunnel Configuration

SSH to your server and edit the tunnel config:

```bash
ssh andrej@192.168.0.113
sudo nano /etc/cloudflared/config.yml
```

## Step 2: Add Rainlo API to Configuration

Your config should look something like this:

```yaml
tunnel: your-tunnel-id-here
credentials-file: /etc/cloudflared/your-tunnel-id.json

ingress:
  # Your existing apps (keep these)
  - hostname: your-existing-app.com
    service: http://localhost:3000
  
  # Add this new rule for Rainlo API
  - hostname: api.rainlo.app
    service: http://localhost:8080
  
  # Keep the catch-all rule at the end
  - service: http_status:404
```

## Step 3: Restart Cloudflare Tunnel

```bash
sudo systemctl restart cloudflared
sudo systemctl status cloudflared
```

## Step 4: Verify Tunnel Status

Check if the tunnel is running and the new hostname is registered:

```bash
# Check service status
sudo systemctl status cloudflared

# Check tunnel logs
sudo journalctl -u cloudflared -f
```

You should see logs indicating that `api.rainlo.app` has been registered.

## Step 5: Test DNS Resolution

From your local machine:

```bash
# Check if DNS is working
nslookup api.rainlo.app

# Test basic connectivity (after deployment)
curl https://api.rainlo.app/api/health
```

## Troubleshooting

### Tunnel Not Starting
```bash
# Check config syntax
cloudflared tunnel ingress validate

# Check tunnel status
cloudflared tunnel info your-tunnel-name
```

### DNS Not Resolving
- Wait 5-10 minutes for DNS propagation
- Check Cloudflare dashboard for DNS records
- Verify tunnel is connected in Cloudflare dashboard

### 502 Bad Gateway
- Check if Docker containers are running: `docker ps`
- Verify nginx is listening on port 8080: `netstat -tlnp | grep 8080`
- Check nginx logs: `docker-compose -f docker-compose.prod.yml logs nginx`

### Connection Refused
- Verify the service URL in tunnel config points to `http://localhost:8080`
- Check if the application is responding locally: `curl http://localhost:8080/api/health`

## Security Notes

- Cloudflare Tunnel automatically handles SSL/TLS
- No need to open ports 80/443 on your router
- Your home IP address remains private
- DDoS protection and WAF are automatically enabled
