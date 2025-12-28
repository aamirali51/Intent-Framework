# Intent Framework - Technical Documentation

> **Version:** 0.3.0  
> **PHP Version:** 8.2+  
> **Architecture:** AI-native, zero-boilerplate micro-framework

---

## 1. Philosophy & Design Principles

| Principle | Implementation |
|-----------|----------------|
| **Zero Boilerplate** | No service containers, providers, or facades |
| **Explicit over Magic** | No annotations, attributes, or hidden behavior |
| **Minimal Abstraction** | Thin wrappers, not deep hierarchies |
| **AI-Native** | Predictable patterns for AI-assisted development |
| **Strict Types** | `declare(strict_types=1)` everywhere |
| **PSR-4** | Standard autoloading via Composer |

---

## 2. Directory Structure

```
INTENT/
├── public/                 # Document root
│   ├── index.php          # Single entry point
│   └── .htaccess          # Apache rewriting
│
├── core/                   # Framework kernel
│   ├── App.php            # Application orchestrator
│   ├── Auth.php           # Authentication
│   ├── Cache.php          # File-based caching
│   ├── Config.php         # Configuration
│   ├── DB.php             # Query builder
│   ├── Event.php          # Event dispatcher
│   ├── Middleware.php     # Middleware interface
│   ├── Pipeline.php       # Middleware execution
│   ├── Request.php        # HTTP request
│   ├── Response.php       # HTTP response
│   ├── Route.php          # Route facade
│   ├── Router.php         # Routing
│   ├── Schema.php         # Dev-only schema
│   ├── Session.php        # Session management
│   └── Validator.php      # Input validation
│
├── app/
│   ├── Api/               # File-based routes
│   ├── Handlers/          # Route handlers
│   ├── Middleware/        # Custom middleware
│   └── Models/            # Data models
│
├── views/                  # Templates
├── storage/                # Cache, logs, uploads
├── tests/                  # PHPUnit tests
├── config.php              # Configuration
├── routes.php              # Route definitions
├── helpers.php             # Global helpers
├── intent                  # CLI tool
└── composer.json           # Autoloading
```

---

## 3. Core Classes

### 3.1 Router & Routes
```php
Route::get('/users', fn($req, $res) => $res->json($users));
Route::post('/users', $handler);
Route::get('/users/{id}', fn($req, $res, $params) => $res->json($params['id']));
Route::get('/admin', $handler)->middleware(AuthMiddleware::class);
```

### 3.2 Request (Immutable)
```php
$request->get('page', 1);      // Query param
$request->post('name');        // POST param
$request->json();              // JSON body
$request->header('auth');      // Header
```

### 3.3 Response (Fluent)
```php
$response->json(['users' => $users]);
$response->json(['error' => 'Not found'], 404);
$response->redirect('/login');
$response->status(201)->json($data);
```

### 3.4 Validator
```php
$v = validate($data, [
    'name' => 'required|min:3|max:255',
    'email' => 'required|email',
    'age' => 'nullable|integer|min:18',
]);

if ($v->fails()) {
    return $res->json(['errors' => $v->errors()], 422);
}
```

### 3.5 Middleware
```php
Route::get('/admin', $handler)->middleware(AuthMiddleware::class);
Route::get('/api', $handler)->middleware([LogMiddleware::class, AuthMiddleware::class]);
```

### 3.6 Session
```php
session('user_id', 123);       // Set
session('user_id');            // Get
flash('success', 'Saved!');    // Flash message
flash('success');              // Get flash
session()->destroy();          // Logout
```

### 3.7 Auth
```php
auth()->attempt(['email' => $email, 'password' => $pass]);
auth()->check();               // Is logged in?
auth()->user();                // Get user
user()['name'];                // User's name
auth()->logout();
Auth::createUser($data);       // Auto-hashes password
```

### 3.8 Events
```php
Event::listen('user.created', fn($user) => sendEmail($user));
event('user.created', $user);
```

### 3.9 Cache
```php
cache('key', $value, 3600);    // Store 1 hour
cache('key');                  // Get
Cache::remember('key', 3600, fn() => expensiveCall());
cache()->flush();              // Clear all
```

### 3.10 DB (Query Builder)
```php
DB::table('users')->get();
DB::table('users')->where('id', 1)->first();
DB::table('users')->insert(['name' => 'John']);
DB::table('users')->where('id', 1)->update(['name' => 'Jane']);
DB::table('users')->where('id', 1)->delete();
```

### 3.11 CSRF Protection
```php
// In forms - use helper
<form method="POST">
    <?= csrf_field() ?>
    <input type="text" name="email">
</form>

// Or manually
<input type="hidden" name="_token" value="<?= csrf_token() ?>">

// AJAX requests - send via header
fetch('/api/data', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken }
});

// Apply middleware to routes
Route::post('/submit', $handler)->middleware(CsrfMiddleware::class);

// Token also accepted from:
// - POST body: _token
// - JSON body: { "_token": "..." }
// - Headers: X-CSRF-TOKEN or X-XSRF-TOKEN
```

---

## 4. Helpers

| Helper | Usage |
|--------|-------|
| `config($key)` | Get config value |
| `view($name, $data)` | Render template |
| `json($data, $status)` | JSON response |
| `redirect($url)` | Redirect |
| `validate($data, $rules)` | Validate input |
| `session($key, $value)` | Get/set session |
| `flash($key, $value)` | Flash message |
| `auth()` | Auth helpers |
| `user()` | Current user |
| `event($name, $data)` | Dispatch event |
| `cache($key, $value)` | Get/set cache |
| `csrf_token()` | Get CSRF token |
| `csrf_field()` | Hidden input field |
| `dd($vars)` | Dump and die |

---

## 5. CLI

```bash
php intent serve          # Start server (port 8080)
php intent serve 3000     # Custom port
php intent cache:clear    # Clear cache
php intent make:handler UserHandler
php intent make:middleware RateLimitMiddleware
php intent help           # Show help
```

---

## 6. Feature Flags

```php
// config.php
'routing.file_routes_first' => false,  // Route order
'feature.schema' => true,              // Auto-schema
'feature.file_routes' => true,         // File-based routing
```

---

## 7. Testing

```bash
composer test              # Run all tests
vendor\bin\phpunit         # Direct run
```

**Test Coverage:**
- 69 tests, 124 assertions
- Config, Response, Router, Validator, Pipeline, Session, Event, Cache

---

## 8. Security

| Feature | Implementation |
|---------|----------------|
| File routes | Outside public/ in app/Api/ |
| Passwords | bcrypt via Auth::hash() |
| SQL | Prepared statements in DB |
| Schema | Dev-only, disabled in prod |
| Sessions | Regenerated on login |
| CSRF | Token validation via middleware |

---

## 9. File Statistics

| File | Purpose |
|------|---------|
| core/App.php | Main orchestrator |
| core/Auth.php | Authentication |
| core/Cache.php | File caching |
| core/Config.php | Configuration |
| core/DB.php | Query builder |
| core/Event.php | Event dispatcher |
| core/Middleware.php | Interface |
| core/Pipeline.php | Middleware runner |
| core/Request.php | HTTP request |
| core/Response.php | HTTP response |
| core/Route.php | Route facade |
| core/Router.php | Routing |
| core/Schema.php | Auto-schema |
| core/Session.php | Sessions |
| core/Validator.php | Validation |

**Total: ~3000 lines of core code**

---

**End of Document**
