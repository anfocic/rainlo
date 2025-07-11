# GitHub Secrets Setup for CI/CD

To enable automatic deployment, you need to set up these secrets in your GitHub repository.

## How to Add Secrets

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**
4. Add each secret below

## Required Secrets

### Server Connection
- **HOST**: `192.168.0.113` (your server IP)
- **USERNAME**: `andrej` (your server username)
- **PORT**: `22` (SSH port, usually 22)
- **SSH_KEY**: Your private SSH key content

### Application Configuration
- **APP_KEY**: Laravel application key (generate with `php artisan key:generate --show`)
- **DB_PASSWORD**: Secure password for MySQL rainlo user
- **MYSQL_ROOT_PASSWORD**: Secure password for MySQL root user

## How to Get Your SSH Key

### If you already have SSH keys:
```bash
cat ~/.ssh/id_rsa
```

### If you need to create SSH keys:
```bash
# Generate new SSH key
ssh-keygen -t rsa -b 4096 -C "your-email@example.com"

# Copy public key to server
ssh-copy-id andrej@192.168.0.113

# Get private key for GitHub secret
cat ~/.ssh/id_rsa
```

## How to Generate APP_KEY

On your local machine or server:
```bash
cd /path/to/rainlo
php artisan key:generate --show
```

Copy the output (including `base64:` prefix) to the APP_KEY secret.

## Example Secret Values

```
HOST: 192.168.0.113
USERNAME: andrej
PORT: 22
SSH_KEY: -----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAlwAAAAdzc2gtcn...
(your full private key content)
-----END OPENSSH PRIVATE KEY-----

APP_KEY: base64:2sz808BitecfFDChCP410Yu4nCOb62tnUDPzBPEyjSc=
DB_PASSWORD: your_secure_database_password_here
MYSQL_ROOT_PASSWORD: your_secure_root_password_here
```

## Testing the Setup

After adding all secrets:

1. Push to master branch
2. Check **Actions** tab in GitHub
3. Watch the deployment process
4. Test the API: `curl https://api.rainlo.app/api/health`

## Troubleshooting

### SSH Connection Issues
- Verify SSH key is correct
- Check server IP and port
- Ensure user has sudo privileges

### Deployment Issues
- Check GitHub Actions logs
- Verify all secrets are set
- Check server logs: `docker-compose -f docker-compose.prod.yml logs`

### API Not Responding
- Check if containers are running: `docker ps`
- Check Cloudflare Tunnel config
- Verify nginx is proxying correctly
