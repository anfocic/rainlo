# Rainlo API Documentation

## 🚀 **Overview**
Rainlo API is a comprehensive financial management system built with Laravel, providing secure endpoints for income/expense tracking, receipt management, and financial reporting.

**Base URL**: `http://localhost:8000/api`  
**Authenticatio n**: Bearer Token (Laravel Sanctum)  
**Content-Type**: `application/json`

---

## 🔐 **Authentication**

### Register User
```http
POST /api/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
    "message": "Registration successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "1|abc123..."
}
```

### Login User
```http
POST /api/login
```

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "token": "2|def456..."
}
```

### Logout User
```http
POST /api/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "message": "Logout successful"
}
```

---

## 💰 **Income Management**

### List Incomes
```http
GET /api/incomes
Authorization: Bearer {token}
```

**Query Parameters:**
- `date_from` (date): Filter from date
- `date_to` (date): Filter to date
- `category` (string): Filter by category
- `is_business` (boolean): Filter business/personal
- `recurring` (boolean): Filter recurring incomes
- `min` (number): Minimum amount
- `max` (number): Maximum amount
- `per_page` (integer): Items per page (default: 15)
- `sort_by` (string): Sort field (date, amount, category)
- `sort_direction` (string): asc/desc

**Response (200):**
```json
{
    "data": [
        {
            "id": 1,
            "amount": "5000.00",
            "description": "Freelance project",
            "category": "Freelance",
            "date": "2024-01-15",
            "is_business": true,
            "recurring": false,
            "user_id": 1
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 1,
        "last_page": 1
    }
}
```

### Create Income
```http
POST /api/incomes
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "amount": 5000.00,
    "description": "Freelance project payment",
    "category": "Freelance",
    "date": "2024-01-15",
    "is_business": true,
    "recurring": false,
    "source": "Client ABC",
    "tax_category": "business_income",
    "notes": "Q1 project completion"
}
```

### Update Income
```http
PUT /api/incomes/{id}
Authorization: Bearer {token}
```

### Delete Income
```http
DELETE /api/incomes/{id}
Authorization: Bearer {token}
```

### Bulk Delete Incomes
```http
POST /api/incomes/bulk-delete
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "ids": [1, 2, 3, 4, 5]
}
```

### Income Statistics
```http
GET /api/incomes/stats
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "data": {
        "total_amount": "15000.00",
        "count": 5,
        "average": "3000.00",
        "business_income": "12000.00",
        "personal_income": "3000.00",
        "recurring_income": "8000.00"
    }
}
```

---

## 💸 **Expense Management**

### List Expenses
```http
GET /api/expenses
Authorization: Bearer {token}
```

**Query Parameters:** (Same as incomes, plus:)
- `vendor` (string): Filter by vendor name
- `tax_deductible` (boolean): Filter tax-deductible expenses

### Create Expense
```http
POST /api/expenses
Authorization: Bearer {token}
```

**Request Body:**
```json
{
    "amount": 250.00,
    "description": "Office supplies",
    "category": "Office",
    "date": "2024-01-10",
    "is_business": true,
    "recurring": false,
    "vendor": "Office Depot",
    "tax_deductible": true,
    "tax_category": "office_supplies",
    "notes": "Printer paper and pens"
}
```

### Expense Statistics
```http
GET /api/expenses/stats
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "data": {
        "total_amount": "8500.00",
        "count": 12,
        "average": "708.33",
        "business_expenses": "7000.00",
        "personal_expenses": "1500.00",
        "recurring_expenses": "2400.00",
        "top_categories": [...],
        "top_vendors": [...]
    }
}
```

---

## 📄 **Receipt Management**

### Upload Receipt
```http
POST /api/receipts/{expense_id}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data:**
- `receipt` (file): PDF, JPG, JPEG, PNG (max 5MB)

**Response (200):**
```json
{
    "message": "Receipt uploaded successfully",
    "data": {
        "receipt_url": "receipts/1/receipt_123.pdf",
        "receipt_full_url": "http://localhost:8000/storage/receipts/1/receipt_123.pdf"
    }
}
```

### Download Receipt
```http
GET /api/receipts/{expense_id}/download
Authorization: Bearer {token}
```

### Delete Receipt
```http
DELETE /api/receipts/{expense_id}
Authorization: Bearer {token}
```

### List All Receipts
```http
GET /api/receipts
Authorization: Bearer {token}
```

---

## 📊 **Tax Reports**

### Annual Tax Report
```http
GET /api/tax-reports/{year}
Authorization: Bearer {token}
```

**Query Parameters:**
- `include_personal` (boolean): Include personal transactions

**Response (200):**
```json
{
    "data": {
        "year": 2024,
        "period": {
            "start_date": "2024-01-01",
            "end_date": "2024-12-31"
        },
        "summary": {
            "total_income": "75000.00",
            "total_expenses": "25000.00",
            "net_income": "50000.00",
            "tax_deductible_expenses": "20000.00",
            "estimated_tax_savings": "5000.00"
        },
        "income": {...},
        "expenses": {...},
        "missing_receipts": 3
    }
}
```

### Quarterly Report
```http
GET /api/tax-reports/{year}/{quarter}
Authorization: Bearer {token}
```

### Tax Deductions Summary
```http
GET /api/tax-reports/{year}/deductions
Authorization: Bearer {token}
```

### Tax Categories
```http
GET /api/tax-reports/categories
Authorization: Bearer {token}
```

---

## 📈 **Dashboard**

### Financial Summary
```http
GET /api/dashboard/summary
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "data": {
        "total_income": "75000.00",
        "total_expenses": "25000.00",
        "net_income": "50000.00",
        "income_count": 45,
        "expense_count": 120,
        "business_income": "60000.00",
        "business_expenses": "20000.00"
    }
}
```

### Recent Transactions
```http
GET /api/dashboard/recent-transactions?limit=10
Authorization: Bearer {token}
```

---

## ❌ **Error Responses**

### Validation Error (422)
```json
{
    "message": "The amount field is required.",
    "errors": {
        "amount": ["The amount field is required."],
        "date": ["The date field is required."]
    }
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "message": "Unauthorized"
}
```

### Not Found (404)
```json
{
    "message": "No query results for model [App\\Models\\Income] 123"
}
```

---

## 🔒 **Security Features**

- **Token-based Authentication**: Laravel Sanctum
- **User Data Isolation**: Users only access their own data
- **Input Validation**: Comprehensive validation on all endpoints
- **File Upload Security**: Type and size restrictions
- **Authorization Checks**: Ownership verification on all operations

---

## 📝 **Rate Limiting**

- **API Requests**: 60 requests per minute per user
- **File Uploads**: 10 uploads per minute per user

---

## 🧪 **Testing**

Run the test suite:
```bash
php artisan test
```

Test specific features:
```bash
php artisan test --filter=IncomeControllerTest
php artisan test --filter=AuthControllerTest
```
