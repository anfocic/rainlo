# Rainlo API

A clean Laravel API backend for financial management applications, built with Laravel 12 and Sanctum for authentication.

## Features

- **Pure API Backend**: No frontend dependencies, perfect for decoupled architecture
- **Laravel Sanctum Authentication**: Token-based authentication for API access
- **User Management**: Registration, login, profile management
- **Clean Architecture**: Simplified codebase focused on API functionality
- **CORS Support**: Ready for frontend integration

## API Endpoints

### Public Endpoints
- `GET /` - API status check
- `POST /api/register` - User registration
- `POST /api/login` - User login

### Protected Endpoints (require Bearer token)
- `GET /api/user` - Get current user
- `GET /api/profile` - Get user profile
- `PATCH /api/profile` - Update user profile
- `DELETE /api/profile` - Delete user account
- `POST /api/logout` - Logout user

## Installation

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy environment file: `cp .env.example .env`
4. Generate application key: `php artisan key:generate`
5. Run migrations: `php artisan migrate`
6. Start the server: `php artisan serve`

## Usage

### Registration
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

### Accessing Protected Endpoints
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Production Deployment

This API is configured for automatic deployment via GitHub Actions to `api.rainlo.app`.

### CI/CD Setup

1. **Server Setup**: Run `./scripts/setup-server.sh` on your server
2. **GitHub Secrets**: Follow `GITHUB_SECRETS_SETUP.md` to configure secrets
3. **Cloudflare Tunnel**: Follow `CLOUDFLARE_TUNNEL_SETUP.md` to configure tunnel
4. **Deploy**: Push to master branch to trigger automatic deployment

### Manual Deployment

```bash
# On your server
cd /opt/rainlo
./scripts/deploy-production.sh
```

### API Endpoints

- **Production**: https://api.rainlo.app
- **Health Check**: https://api.rainlo.app/api/health

## Architecture

- **Backend**: Laravel API (this repository)
- **Frontend**: Next.js on Cloudflare Pages (rainlo.app)
- **Database**: MySQL with Redis caching
- **Deployment**: Docker + GitHub Actions
- **SSL/CDN**: Cloudflare Tunnel
