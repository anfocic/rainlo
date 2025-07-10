# GitHub Secrets Setup Guide

After setting up your home server, you need to configure GitHub repository secrets for automated deployment.

## 🔑 Required Secrets

Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Add these secrets:

### 1. HOST
- **Name**: `HOST`
- **Value**: Your server's IP address (e.g., `192.168.1.100` or your public IP)

### 2. USERNAME
- **Name**: `USERNAME`
- **Value**: `smartax`

### 3. SSH_KEY
- **Name**: `SSH_KEY`
- **Value**: The private SSH key from your server

To get the SSH key, run this on your server:
```bash
sudo cat /home/smartax/.ssh/id_rsa
```

Copy the entire output including the `-----BEGIN OPENSSH PRIVATE KEY-----` and `-----END OPENSSH PRIVATE KEY-----` lines.

### 4. PORT
- **Name**: `PORT`
- **Value**: `22` (or your custom SSH port)

## 🌐 Environment Setup

### Create Production Environment

1. Go to your GitHub repository → **Settings** → **Environments**
2. Click **New environment**
3. Name it `production`
4. Add protection rules if desired (e.g., require manual approval)

## 🔧 Server IP Discovery

If you don't know your server's IP address:

### Internal IP (for local network):
```bash
hostname -I | awk '{print $1}'
```

### External IP (for internet access):
```bash
curl -s ifconfig.me
```

## 🔒 Security Notes

- **Never share your private SSH key**
- **Use a dedicated deployment user** (smartax)
- **Consider using SSH key passphrases** for additional security
- **Regularly rotate your SSH keys**
- **Monitor deployment logs** for suspicious activity

## ✅ Testing the Setup

After adding all secrets, you can test the deployment by:

1. Making a small change to your code
2. Committing and pushing to the `main` branch:
   ```bash
   git add .
   git commit -m "Test deployment setup"
   git push origin main
   ```
3. Check the **Actions** tab in your GitHub repository
4. Watch the deployment process in real-time

## 🚨 Troubleshooting

### SSH Connection Issues
- Verify the server IP is correct
- Check if SSH port 22 is open: `telnet your-server-ip 22`
- Ensure the SSH key is correctly formatted (no extra spaces/newlines)

### Permission Issues
- Make sure the `smartax` user has Docker permissions
- Verify `/opt/smartax` directory ownership: `ls -la /opt/`

### Deployment Failures
- Check GitHub Actions logs for detailed error messages
- SSH into your server and check: `journalctl -u smartax -f`
- Verify Docker is running: `sudo systemctl status docker`

## 📞 Quick Commands Reference

### On your server:
```bash
# Check deployment status
sudo systemctl status smartax

# View application logs
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml logs -f

# Manual deployment
sudo -u smartax /opt/smartax/deploy-server.sh

# Check container status
sudo -u smartax docker-compose -f /opt/smartax/docker-compose.prod.yml ps
```

### Test health endpoint:
```bash
curl http://your-server-ip:8080/up
```

## 🎉 Success!

Once everything is set up, every push to your `main` branch will automatically:

1. ✅ Run tests
2. ✅ Build Docker image
3. ✅ Push to GitHub Container Registry
4. ✅ Deploy to your home server
5. ✅ Run database migrations
6. ✅ Perform health checks

Your SmartTax API will be available at `http://your-server-ip:8080`!
