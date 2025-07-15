# Rainlo - Financial Management API

A modern, production-ready Laravel API backend for financial management applications, built with Laravel 12 and deployed via Docker. This project showcases clean architecture, best practices, and modern development workflows.

## 🚀 Technology Stack

### Backend
- **PHP 8.2**: Modern PHP with type hints and latest features
- **Laravel 12**: Latest version of the Laravel framework
- **PostgreSQL 15**: Robust relational database
- **Docker & Docker Compose**: Containerized deployment
- **Nginx**: High-performance web server
- **Supervisor**: Process monitoring

### Authentication & Security
- **Laravel Sanctum**: Token-based API authentication
- **CORS Support**: Configured for secure cross-origin requests
- **Cloudflare Tunnel**: Secure edge deployment without exposing ports

### Testing & Quality
- **Pest PHP**: Modern testing framework for PHP
- **Laravel Pint**: PHP code style fixer
- **Scribe**: API documentation generator

### CI/CD
- **GitHub**: Code storage and future CI/CD
- **Docker Hub**: Container registry for production images

## ✨ Features

- **Pure API Backend**: Decoupled architecture for modern frontend integration
- **Authentication System**: Registration, login, password reset
- **Transaction Management**: Create, read, update, delete financial transactions
- **Tax Calculator**: Irish tax calculation system with comparison features
- **Dashboard Analytics**: Financial summaries and statistics
- **Structured API Responses**: Consistent response format with proper error handling
- **Domain-Driven Design**: Clean separation of concerns

## 📊 API Endpoints

### Public Endpoints
- `GET /api/health` - API health check
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User authentication
- `POST /api/auth/forgot-password` - Password reset request
- `POST /api/auth/reset-password` - Password reset confirmation

### Protected Endpoints (require Bearer token)
- `GET /api/user` - Get current user
- `POST /api/auth/logout` - Logout user
- `GET /api/auth/me` - Get authenticated user details

#### Transactions
- `GET /api/transactions` - List all transactions
- `POST /api/transactions` - Create new transaction
- `GET /api/transactions/stats` - Get transaction statistics
- `GET /api/transactions/{id}` - Get transaction details
- `PUT/PATCH /api/transactions/{id}` - Update transaction
- `DELETE /api/transactions/{id}` - Delete transaction
- `POST /api/transactions/bulk-delete` - Delete multiple transactions

#### Tax Calculator
- `POST /api/tax/calculate` - Calculate taxes
- `GET /api/tax/rates` - Get tax rates
- `POST /api/tax/compare` - Compare tax scenarios
- `POST /api/tax/marginal-rate` - Calculate marginal tax rate

#### Dashboard
- `GET /api/dashboard/summary` - Get financial summary
- `GET /api/dashboard/recent-transactions` - Get recent transactions

## 🏗️ Best Practices Implemented

### Code Quality
- **Domain-Driven Design**: Organized code into domain folders for better separation of concerns
- **Abstract Controllers**: Base controller with structured response handling
- **Form Requests**: Validation logic separated into dedicated request classes
- **Service Layer**: Business logic abstracted into service classes
- **Repository Pattern**: Data access layer abstraction

### API Design
- **Consistent Response Format**: All endpoints return structured JSON responses
- **Error Handling**: Centralized error handling with proper HTTP status codes
- **API Documentation**: Auto-generated documentation with Scribe
- **RESTful Routes**: Following REST conventions for resource endpoints

### Security
- **Token-based Authentication**: Secure API access with Laravel Sanctum
- **Input Validation**: Comprehensive validation on all endpoints
- **CORS Configuration**: Properly configured for frontend integration
- **Environment Variables**: Sensitive data stored in environment variables

### Testing
- **Pest PHP**: Modern testing framework with expressive syntax
- **Feature Tests**: Comprehensive API endpoint testing
- **Test Database**: Isolated test environment

## 🚀 Production Deployment

This application is deployed to production using Docker containers and Cloudflare Tunnel.

### Deployment Architecture
- **Containerized**: Docker images built and pushed to Docker Hub
- **Database**: PostgreSQL 15 with persistent volumes
- **Web Server**: Nginx with PHP-FPM
- **Process Management**: Supervisor for process monitoring
- **SSL/CDN**: Cloudflare Tunnel for secure edge deployment

### Production URLs
- **API**: https://api.rainlo.app
- **Health Check**: https://api.rainlo.app/api/health
- **Frontend**: https://rainlo.app (Next.js on Cloudflare Pages)
