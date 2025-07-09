# Laravel API Development - Interview Study Guide

## 🎯 **Project Overview**
**SmartTax API** - A Laravel-based tax management system with clean API architecture, token-based authentication, and comprehensive financial tracking.

---

## 🏗️ **Architecture & Design Patterns**

### **API-First Architecture**
- **Pure API Backend**: Laravel serves only JSON responses
- **Stateless Authentication**: Token-based using Laravel Sanctum
- **Frontend Agnostic**: Designed to work with Next.js or any frontend framework

### **Controller Design Pattern**
```php
// ✅ GOOD: Consolidated AuthController
class AuthController extends Controller {
    public function login()    // Clear, intuitive naming
    public function register() // All auth logic in one place
    public function logout()   // Easy to maintain
}

// ❌ Laravel Default: Separate controllers
// AuthenticatedSessionController, RegisteredUserController
```

### **Request Validation Pattern**
```php
// ✅ Form Requests for validation
class IncomeRequest extends FormRequest {
    public function rules(): array
    public function messages(): array
    protected function prepareForValidation(): void
}

// ✅ Separate requests for different operations
// IncomeRequest (create/update) vs IncomeFilterRequest (filtering)
```

---

## 🔐 **Authentication & Security**

### **Laravel Sanctum Implementation**
```php
// Token-based authentication
$token = $user->createToken('auth-token')->plainTextToken;

// Middleware protection
Route::middleware('auth:sanctum')->group(function () {
    // Protected routes
});
```

### **CSRF Protection Resolution**
**Problem**: Frontend getting CSRF token mismatch errors
**Solution**: 
1. Removed `EnsureFrontendRequestsAreStateful` middleware
2. Cleared stateful domains in `config/sanctum.php`
3. Pure token-based authentication (no sessions/CSRF needed)

### **Authorization Patterns**
```php
// User ownership verification
if ($income->user_id !== auth()->id()) {
    return response()->json(['message' => 'Unauthorized'], 403);
}

// Scope queries to authenticated user
Income::forUser(auth()->id())
```

---

## 🗄️ **Database & Eloquent**

### **Model Scopes (Query Builder Pattern)**
```php
// ✅ Chainable, reusable query scopes
public function scopeForUser($query, $user_id) {
    return $query->where('user_id', $user_id);
}

public function scopeDateRange($query, $from, $to) {
    if ($from) $query->where('date', '>=', $from);
    if ($to) $query->where('date', '<=', $to);
    return $query;
}

// Usage: Income::forUser(auth()->id())->dateRange($from, $to)
```

### **Eloquent Relationships**
```php
// User has many incomes/expenses
public function incomes(): HasMany {
    return $this->hasMany(Income::class);
}

// Income belongs to user
public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}
```

### **Model Attributes & Casting**
```php
protected $casts = [
    'date' => 'date',
    'is_business' => 'boolean',
    'amount' => 'decimal:2',
    'password' => 'hashed', // Automatic password hashing
];
```

---

## 🎛️ **API Design & Best Practices**

### **RESTful Resource Controllers**
```php
// Standard REST endpoints
GET    /api/incomes          // index()
POST   /api/incomes          // store()
GET    /api/incomes/{id}     // show()
PUT    /api/incomes/{id}     // update()
DELETE /api/incomes/{id}     // destroy()

// Custom endpoints
POST   /api/incomes/bulk-delete
GET    /api/incomes-stats
```

### **Consistent Response Format**
```php
// Success responses
return response()->json([
    'data' => $resource,
    'message' => 'Operation successful'
], 201);

// Paginated responses
return response()->json([
    'data' => $items->items(),
    'pagination' => [
        'current_page' => $items->currentPage(),
        'total' => $items->total(),
        // ...
    ]
]);
```

### **Advanced Filtering & Pagination**
```php
// Flexible filtering with validation
$query = Income::forUser(auth()->id())
    ->dateRange($request->date_from, $request->date_to)
    ->category($request->category)
    ->amountRange($request->min, $request->max);

// Configurable pagination
->paginate($request->per_page ?? 15)
```

---

## 🛠️ **Laravel Features Demonstrated**

### **Form Requests**
- **Validation Logic**: Centralized in dedicated request classes
- **Authorization**: Built-in authorization methods
- **Data Preparation**: `prepareForValidation()` for data sanitization
- **Custom Messages**: User-friendly error messages

### **Middleware**
- **Authentication**: `auth:sanctum` middleware
- **CORS**: Configured for API access
- **Request Transformation**: Data cleaning and validation

### **Service Container & Dependency Injection**
```php
// Automatic dependency injection
public function store(IncomeRequest $request): JsonResponse
// Laravel automatically validates and injects the request
```

---

## 🚀 **Performance & Optimization**

### **Query Optimization**
```php
// Efficient filtering with conditional queries
public function scopeCategory($query, $category) {
    if ($category) {
        return $query->where('category', $category);
    }
    return $query; // No unnecessary WHERE clause
}
```

### **Bulk Operations**
```php
// Bulk delete with single query
$deletedCount = Income::whereIn('id', $request->ids)
    ->where('user_id', auth()->id())
    ->delete();
```

---

## 🧪 **Testing Considerations**

### **API Testing Strategy**
- **Feature Tests**: Test complete API endpoints
- **Unit Tests**: Test individual model scopes and methods
- **Authentication Tests**: Verify token-based auth works
- **Authorization Tests**: Ensure users can only access their data

### **Test Examples**
```php
// Test authenticated endpoint
$response = $this->actingAs($user)
    ->postJson('/api/incomes', $incomeData);

$response->assertStatus(201)
    ->assertJsonStructure(['data', 'message']);
```

---

## 💡 **Key Interview Talking Points**

### **Why This Architecture?**
1. **Scalability**: API-first allows multiple frontends
2. **Maintainability**: Clear separation of concerns
3. **Security**: Token-based auth, proper authorization
4. **Developer Experience**: Intuitive naming, consistent patterns

### **Problem-Solving Examples**
1. **CSRF Issue**: Diagnosed middleware conflict, implemented pure token auth
2. **Controller Naming**: Chose intuitive names over Laravel defaults
3. **Validation Strategy**: Form Requests for clean, reusable validation

### **Laravel Best Practices Applied**
- ✅ Eloquent relationships and scopes
- ✅ Form Request validation
- ✅ Resource controllers with custom endpoints
- ✅ Proper middleware usage
- ✅ Consistent API responses
- ✅ Security-first approach (user isolation)

---

## 🎯 **Demonstration Points**

1. **Show API endpoints working** (Postman/curl examples)
2. **Explain authentication flow** (register → login → token → protected routes)
3. **Demonstrate filtering system** (date ranges, categories, amounts)
4. **Discuss security measures** (user isolation, authorization checks)
5. **Explain code organization** (controllers, requests, models, routes)

---

## 📋 **API Endpoints Reference**

### **Authentication Endpoints**
```bash
POST /api/register
POST /api/login
POST /api/logout (requires token)
POST /api/forgot-password
POST /api/reset-password
```

### **Income Management**
```bash
GET    /api/incomes              # List with filtering
POST   /api/incomes              # Create new income
GET    /api/incomes/{id}         # Show specific income
PUT    /api/incomes/{id}         # Update income
DELETE /api/incomes/{id}         # Delete income
POST   /api/incomes/bulk-delete  # Delete multiple
GET    /api/incomes-stats        # Income statistics
```

### **Expense Management**
```bash
GET    /api/expenses             # List with filtering
POST   /api/expenses             # Create new expense
GET    /api/expenses/{id}        # Show specific expense
PUT    /api/expenses/{id}        # Update expense
DELETE /api/expenses/{id}        # Delete expense
POST   /api/expenses/bulk-delete # Delete multiple
GET    /api/expenses-stats       # Expense statistics
```

---

## 🔧 **Technical Implementation Details**

### **Request/Response Examples**

#### **Create Income**
```json
POST /api/incomes
{
    "amount": 5000.00,
    "description": "Freelance project payment",
    "category": "Freelance",
    "date": "2024-01-15",
    "is_business": true,
    "recurring": false
}

Response:
{
    "message": "Income created successfully",
    "data": {
        "id": 1,
        "amount": "5000.00",
        "description": "Freelance project payment",
        "category": "Freelance",
        "date": "2024-01-15",
        "is_business": true,
        "recurring": false,
        "user_id": 1,
        "created_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

#### **Filter Incomes**
```bash
GET /api/incomes?date_from=2024-01-01&date_to=2024-01-31&category=Freelance&is_business=true&min=1000&max=10000&per_page=20&sort_by=amount&sort_direction=desc
```

### **Error Handling**
```json
// Validation Error (422)
{
    "message": "The amount field is required.",
    "errors": {
        "amount": ["The amount field is required."]
    }
}

// Unauthorized (403)
{
    "message": "Unauthorized"
}

// Not Found (404)
{
    "message": "No query results for model [App\\Models\\Income] 123"
}
```

---

## 🎤 **Interview Questions & Answers**

### **Q: Why did you choose this controller structure over Laravel's default?**
**A:** I chose a consolidated `AuthController` over Laravel's separate controllers (`AuthenticatedSessionController`, `RegisteredUserController`) because:
- **Intuitive naming**: `login()`, `register()`, `logout()` vs `store()`, `destroy()`
- **Single responsibility**: All auth logic in one place
- **API-first approach**: Better suited for API-only applications
- **Easier maintenance**: One controller to manage instead of multiple

### **Q: How did you solve the CSRF token mismatch issue?**
**A:** The issue was Sanctum treating frontend requests as "stateful" instead of "stateless":
1. **Root cause**: `EnsureFrontendRequestsAreStateful` middleware was applying session-based auth
2. **Solution**: Removed the middleware and cleared stateful domains
3. **Result**: Pure token-based authentication without CSRF requirements
4. **Benefits**: Cleaner API, better frontend compatibility

### **Q: Explain your validation strategy.**
**A:** I used Laravel Form Requests for centralized validation:
- **Separation of concerns**: Validation logic separate from controllers
- **Reusability**: Same request class for create/update operations
- **Custom logic**: Data preparation and conditional validation
- **Better UX**: Custom error messages and attribute names
- **Type safety**: IDE support and automatic injection

### **Q: How do you ensure data security?**
**A:** Multiple security layers:
- **Authentication**: Token-based with Laravel Sanctum
- **Authorization**: User ownership verification on every operation
- **Query scoping**: `forUser()` scope ensures data isolation
- **Input validation**: Form Requests validate all user input
- **SQL injection prevention**: Eloquent ORM with parameter binding

### **Q: Describe your API design philosophy.**
**A:** RESTful design with practical extensions:
- **Standard REST**: GET, POST, PUT, DELETE for CRUD operations
- **Custom endpoints**: `/bulk-delete`, `/stats` for specific needs
- **Consistent responses**: Standardized JSON structure
- **Flexible filtering**: Query parameters for complex searches
- **Pagination**: Configurable page sizes with metadata

---

## 🚀 **Advanced Features Implemented**

### **Dynamic Query Building**
```php
// Chainable scopes for flexible filtering
$query = Income::forUser(auth()->id())
    ->when($request->date_from, fn($q) => $q->where('date', '>=', $request->date_from))
    ->when($request->category, fn($q) => $q->where('category', $request->category))
    ->when($request->is_business !== null, fn($q) => $q->where('is_business', $request->is_business));
```

### **Statistics & Analytics**
```php
// Complex aggregation queries
$stats = [
    'total_amount' => $query->sum('amount'),
    'average' => $query->avg('amount'),
    'top_categories' => Income::selectRaw('category, SUM(amount) as total')
        ->groupBy('category')
        ->orderByDesc('total')
        ->limit(5)
        ->get()
];
```

### **Bulk Operations**
```php
// Efficient bulk operations with authorization
$deletedCount = Income::whereIn('id', $request->ids)
    ->where('user_id', auth()->id()) // Security: only user's records
    ->delete();
```

This project demonstrates **production-ready Laravel API development** with modern best practices! 🚀
