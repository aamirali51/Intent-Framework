# Intent Framework - Technical Documentation

> **Version:** 0.8.0  
> **PHP Version:** 8.2+  
> **Architecture:** AI-native, zero-boilerplate micro-framework

---

## 1. Philosophy & Design Principles

| Principle | Implementation |
|-----------|----------------|
| **Zero Boilerplate** | No service providers or facades |
| **Explicit over Magic** | No annotations, attributes, or hidden behavior |
| **Minimal Abstraction** | Thin wrappers, not deep hierarchies |
| **AI-Native** | Predictable patterns for AI-assisted development |
| **Strict Types** | `declare(strict_types=1)` everywhere |
| **PSR-4** | Standard autoloading via Composer |
| **PSR-3 Compatible** | Logging follows PSR-3 log levels |
| **PSR-16 Compatible** | Cache implements SimpleCache interface |

---

## Service Access Pattern (Canonical since v0.8.0)

All services should be accessed via **registry-backed helper functions**. This provides:
- **Consistency**: Single access pattern across the codebase
- **Testability**: Easy to mock via Registry
- **Future-proofing**: Decouples from static classes

### Canonical Helpers

```php
// Database
db()->table('users')->where('id', 1)->first();
db()->raw('SELECT * FROM users');
db()->transaction(fn() => /* ... */);

// Authentication
auth()->check();
auth()->user();
auth()->attempt(['email' => $email, 'password' => $pass]);

// Cache
cache('key');                      // Get
cache('key', $value, 3600);        // Set for 1 hour
cache()->flush();                  // Clear all

// Session
session('user_id');                // Get
session('user_id', 123);           // Set
session()->destroy();              // Destroy

// Configuration
config('app.name');
config('db.driver', 'mysql');      // With default

// Logging
logger()->info('User logged in');
logger()->error('Payment failed', ['order' => 123]);

// Request/Response
request()->post('name');
response()->json(['success' => true]);

// Application
app()->router();
```

### Deprecated Patterns (Avoid)

Static facade calls are deprecated and will be removed in v2.0:

```php
// ❌ Deprecated - avoid in new code
DB::table('users')->get();
Cache::put('key', $value);

// ✅ Use helper functions instead
db()->table('users')->get();
cache('key', $value);
```

> **Note**: `Route::get()` and `Route::post()` for route definitions are NOT deprecated.
> Only service access patterns are being standardized.

---

## 2. Directory Structure

```
INTENT/
├── public/                 # Document root
│   ├── index.php          # Single entry point
│   └── .htaccess          # Apache rewriting
│
├── src/                    # Framework source (PDS)
│   ├── Core/              # Framework kernel
│   │   ├── App.php        # Application orchestrator
│   │   ├── Auth.php       # Authentication
│   │   ├── Cache.php      # File-based caching
│   │   ├── Config.php     # Configuration
│   │   ├── DB.php         # Connection manager + facade
│   │   ├── QueryBuilder.php # Query building + execution
│   │   ├── Event.php      # Event dispatcher
│   │   ├── Log.php        # PSR-3 compatible logging
│   │   ├── Middleware.php # Middleware interface
│   │   ├── Migration.php  # Database migrations
│   │   ├── Package.php    # Package discovery
│   │   ├── Paginator.php  # Pagination
│   │   ├── Pipeline.php   # Middleware execution
│   │   ├── RateLimiter.php # Rate limiting
│   │   ├── Registry.php   # Service registry
│   │   ├── Request.php    # HTTP request
│   │   ├── Response.php   # HTTP response
│   │   ├── Route.php      # Route facade
│   │   ├── Router.php     # Routing
│   │   ├── Schema.php     # Dev-only schema
│   │   ├── SecurityHeaders.php # Security headers middleware
│   │   ├── Session.php    # Session management
│   │   ├── Upload.php     # File uploads
│   │   ├── Validator.php  # Input validation
│   │   └── Exceptions/    # Custom exceptions
│   │       └── HttpExceptions.php
│   └── helpers.php        # Global helpers
│
├── config/                 # Configuration files
│   ├── app.php            # Application config
│   └── routes.php         # Route definitions
│
├── app/                    # User application code
│   ├── Api/               # File-based routes
│   ├── Handlers/          # Route handlers
│   ├── Middleware/        # Custom middleware
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   ├── LogMiddleware.php
│   │   └── RateLimitMiddleware.php
│   └── Models/            # Data models
│
├── database/               # Database files
│   └── migrations/        # Migration files
│
├── resources/              # Application resources
│   └── views/             # Templates
│
├── storage/                # Cache, logs, uploads
├── tests/                  # PHPUnit tests
├── intent                  # CLI tool
└── composer.json           # Autoloading (PSR-4)
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
$request->ip();                // Client IP
$request->path;                // Request path
```

### 3.3 Response (Fluent)
```php
$response->json(['users' => $users]);
$response->json(['error' => 'Not found'], 404);
$response->redirect('/login');
$response->status(201)->json($data);
$response->header('X-Custom', 'value');
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
Auth::setUser($data);          // Set user for API requests (stateless)
```

### 3.8 API Tokens
```php
// Generate a token (returns plain token - show once!)
$token = ApiToken::create($userId);
$token = ApiToken::create($userId, 'mobile-app', 3600); // With name + TTL

// Validate token from request
$user = ApiToken::validate($token);       // Returns user or null
$token = ApiToken::fromRequest($request); // Extract Bearer token

// Revoke tokens
ApiToken::revoke($tokenId);               // Single token
ApiToken::revokeToken($plainToken);       // By token value
ApiToken::revokeAll($userId);             // All user tokens

// Token management
ApiToken::userTokens($userId);            // List user's tokens
ApiToken::pruneExpired();                 // Clean up expired tokens
ApiToken::check($token);                  // Quick validation check

// Helper function
$token = api_token($userId, 'api-key', 86400);
```

**Storage:** Tokens stored hashed (SHA-256) in `api_tokens` table.

**AuthMiddleware:** Automatically checks both session and Bearer token:
```php
// Both work:
Route::get('/api/me', $handler)->middleware(AuthMiddleware::class);

// Session auth: Uses session cookie
// API auth: Authorization: Bearer <token>
```

### 3.9 OAuth (Social Login)
```php
// Redirect to provider
return redirect(OAuth::redirect('google'));
return redirect(OAuth::redirect('github', ['scope' => 'user:email']));

// Handle callback
$userData = OAuth::callback('google', $code, $state);
// Returns: ['id', 'email', 'name', 'avatar', 'provider', 'raw']

// Check provider support
OAuth::providers();              // ['google', 'github', 'facebook']
OAuth::hasProvider('google');    // true if configured

// Helper function
return redirect(oauth_redirect('google'));
```

**Configuration:** `config/auth.php`
```php
'oauth' => [
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => '/auth/google/callback',
    ],
]
```

### 3.10 Events
```php
Event::listen('user.created', fn($user) => sendEmail($user));
event('user.created', $user);
```

### 3.9 Cache (PSR-16 SimpleCache)

**PSR-16 Compliant**: Implements `Psr\SimpleCache\CacheInterface`

```php
// Static API (convenience)
Cache::put('key', $value, 3600);       // Store for 1 hour
Cache::pull('key');                     // Retrieve
Cache::exists('key');                   // Check existence
Cache::forget('key');                   // Remove
Cache::flush();                         // Clear all
Cache::remember('key', 3600, fn() => expensiveCall());

// PSR-16 Instance API
$cache = Cache::instance();
$cache->set('key', $value, 3600);       // Store
$cache->get('key');                      // Retrieve
$cache->has('key');                      // Check existence
$cache->delete('key');                   // Remove
$cache->clear();                         // Clear all
$cache->getMultiple(['a', 'b']);         // Bulk get
$cache->setMultiple(['a' => 1, 'b' => 2]); // Bulk set
$cache->deleteMultiple(['a', 'b']);      // Bulk delete

// TTL supports DateInterval
$cache->set('key', 'value', new DateInterval('PT1H'));
Cache::remember('key', new DateInterval('P1D'), $callback);

// Helper function
cache('key', $value, 3600);            // Store
cache('key');                          // Get
cache()->flush();                      // Clear all
```

**Key Validation**: PSR-16 reserved characters `{}()/\@:` throw `InvalidCacheKeyException`.

### 3.10 DB (Query Builder)

**Architecture: Separated Concerns**

```
DB (static facade + connection manager)
  └─> QueryBuilder (query building + execution)
```

**DB Class (`Core\DB`):**
- PDO connection management
- Static facade for `table()` method
- Raw SQL execution
- Driver name access

**QueryBuilder Class (`Core\QueryBuilder`):**
- Query construction (SELECT, WHERE, JOIN, etc.)
- Query execution (get, first, insert, update, delete)
- Type casting and identifier escaping
- Result retrieval

```php
// Basic queries
DB::table('users')->get();
DB::table('users')->where('id', 1)->first();
DB::table('users')->insert(['name' => 'John']);
DB::table('users')->where('id', 1)->update(['name' => 'Jane']);
DB::table('users')->where('id', 1)->delete();

// OR conditions
DB::table('posts')
    ->where('status', 'published')
    ->orWhere('featured', 1)
    ->get();

// Type casting (automatic)
DB::table('posts')->insert([
    'title' => 'Post',
    'published_at' => new DateTime('2024-01-01'),
    'is_active' => true
]);

// Multi-database support
// MySQL, PostgreSQL, SQLite with automatic identifier escaping
```

#### Why Separate DB and QueryBuilder?

**Previous Architecture (v0.4.0):**
```
DB (combined: connection + query building + execution)
```

**Current Architecture (v0.5.0+):**
```
DB (connection manager + facade)
  └─> QueryBuilder (query building + execution)
```

**Benefits:**
- ✅ **Single Responsibility**: Each class has one clear purpose
- ✅ **Better Testability**: Can test QueryBuilder in isolation
- ✅ **More Extensible**: Easy to add custom query builders
- ✅ **Industry Standard**: Follows Laravel, Doctrine, Eloquent patterns
- ✅ **Cleaner Code**: Smaller, more focused classes
- ✅ **Backward Compatible**: API remains identical

**Trade-offs:**
- Slightly more complex (2 classes instead of 1)
- Minimal (QueryBuilder uses `DB::connection()` internally)

**Decision Rationale:**
After community feedback, we adopted the industry standard pattern. The separation provides significant benefits with minimal complexity cost. The static facade pattern keeps the API simple while maintaining proper separation of concerns.

**Identifier Escaping Strategy**

Automatic escaping of all identifiers based on detected database driver:
- **MySQL**: Backticks `` `table_name` `` (internal `` ` `` doubled)
- **PostgreSQL/SQLite**: Double quotes `"table_name"` (internal `"` doubled)
- **Backslashes**: Escaped in all drivers

**Security**: Uses `strtr()` to prevent SQL injection via malicious identifier names.

```php
// Attack attempt (blocked):
DB::table('users`; DROP TABLE users; --')->get();
// Escaped to: `users``; DROP TABLE users; --`
```

**Operator Whitelist**

All SQL operators are validated against a strict whitelist to prevent injection:

```php
const ALLOWED_OPERATORS = [
    '=', '!=', '<>', '<', '>', '<=', '>=',
    'LIKE', 'NOT LIKE', 'ILIKE',
    'REGEXP', 'NOT REGEXP', 'RLIKE',
    'IS', 'IS NOT', 'IN', 'NOT IN',
    'BETWEEN', 'NOT BETWEEN',
];
```

Invalid operators throw `InvalidArgumentException`:

```php
// Attack attempt (blocked):
->where('id', '1; DROP TABLE users;--', 'x')
// Throws: InvalidArgumentException: Invalid SQL operator
```

**Type Casting Approach**

Automatic type casting for common PHP types to database equivalents:
- `DateTimeInterface` → `Y-m-d H:i:s` string
- `bool` → `1`/`0` integer with `PDO::PARAM_INT`
- `null` → `NULL` with `PDO::PARAM_NULL`
- `int` → integer with `PDO::PARAM_INT`
- `float` → string with `PDO::PARAM_STR` (PDO limitation)

Benefits: Reduces developer friction, prevents common bugs.

**Reference**: Design decisions based on community feedback and industry best practices.

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

### 3.12 Route Groups
```php
// Group with prefix
Route::group(['prefix' => '/admin'], function () {
    Route::get('/dashboard', $handler);  // /admin/dashboard
    Route::get('/users', $handler);      // /admin/users
});

// Group with middleware
Route::group(['middleware' => AuthMiddleware::class], function () {
    Route::get('/profile', $handler);
    Route::post('/settings', $handler);
});

// Shorthand methods
Route::prefix('/api/v1', function () {
    Route::get('/users', $handler);      // /api/v1/users
});

Route::middleware(CsrfMiddleware::class, function () {
    Route::post('/submit', $handler);
});

// Nested groups
Route::group(['prefix' => '/api'], function () {
    Route::group(['prefix' => '/v1', 'middleware' => ApiMiddleware::class], function () {
        Route::get('/users', $handler);  // /api/v1/users
    });
});
```

---

### 3.13 Rate Limiting

**Two approaches available:**

#### Static Class (Core\RateLimiter)
```php
// Apply as middleware with fluent factories
Route::post('/login', $handler)->middleware(RateLimiter::perMinute(5));
Route::post('/api/send', $handler)->middleware(RateLimiter::perHour(100));
Route::post('/heavy', $handler)->middleware(RateLimiter::perDay(10));

// Or with custom limits
Route::post('/api', $handler)->middleware(RateLimiter::middleware(100, 300)); // 100 requests per 5 minutes

// Manual usage in code
if (RateLimiter::tooManyAttempts('login:' . $ip, 5)) {
    return response()->json(['error' => 'Too many attempts'], 429);
}
RateLimiter::hit('login:' . $ip, 60);
RateLimiter::clear('login:' . $ip);
```

#### Middleware Class (App\Middleware\RateLimitMiddleware)
```php
// Default: 60 requests per minute
Route::post('/api/login', $handler)->middleware(RateLimitMiddleware::class);

// Custom limits (5 requests per 60 seconds)
Route::post('/api/login', $handler)->middleware(new RateLimitMiddleware(5, 60));
```

**Response headers added:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 57
Retry-After: 45  (when rate limited)
```

**Exceeding limit returns 429 Too Many Requests**

**Storage**: File-based - no Redis required. Stores in `storage/cache/ratelimit/`.

### 3.14 Logging (PSR-3 Compatible)
```php
// All PSR-3 log levels supported
Log::emergency('System down');
Log::alert('Action needed');
Log::critical('Database connection lost');
Log::error('Payment failed', ['order_id' => 456]);
Log::warning('Low disk space');
Log::notice('User updated profile');
Log::info('User logged in', ['user_id' => 123]);
Log::debug('Debug message');

// Using helper functions
logger()->info('Message');
logger('error', 'Something failed');
log_message('info', 'User login', ['id' => 1]);

// Log management
Log::read();              // Read today's log
Log::read('2024-01-15');  // Read specific date
Log::dates();             // Get available log dates
Log::clear();             // Clear all logs

// Logs stored in storage/logs/YYYY-MM-DD.log
```

### 3.15 Package Auto-Discovery
```php
// Packages define providers in composer.json:
// "extra": {
//     "intent": {
//         "providers": ["Vendor\\Package\\ServiceProvider"]
//     }
// }

// Boot all discovered packages
Package::boot();

// Manual registration
Package::register(MyServiceProvider::class);

// Check packages
Package::all();           // All discovered packages
Package::has('vendor/pkg'); // Check if discovered
```

### 3.16 Database Migrations
```php
// Create a migration
php intent make:migration create_users_table

// Run pending migrations
php intent migrate

// Rollback last batch
php intent migrate:rollback
```

**Migration file example:**
```php
<?php
declare(strict_types=1);

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE users");
    }
};
```

**Philosophy**: No schema builder - just raw SQL. Simple and powerful.

**Features:**
- Batch-based rollbacks
- Automatic migrations table creation
- MySQL/PostgreSQL/SQLite support
- Query helper with bindings: `$this->query($sql, $bindings)`

### 3.17 Custom Exceptions

Located in `Core\Exceptions\HttpExceptions.php`:

```php
use Core\Exceptions\NotFoundException;
use Core\Exceptions\UnauthorizedException;
use Core\Exceptions\ForbiddenException;
use Core\Exceptions\ValidationException;
use Core\Exceptions\TooManyRequestsException;
use Core\Exceptions\ServerException;
use Core\Exceptions\MaintenanceException;

// Throw when resource not found
throw new NotFoundException('User not found');

// Throw when authentication required
throw new UnauthorizedException('Please login');

// Throw when authenticated but not allowed
throw new ForbiddenException('Admin access required');

// Throw with validation errors
throw new ValidationException(['email' => ['Invalid email format']]);

// Throw when rate limited
throw new TooManyRequestsException(60, 'Try again later');

// Throw for server errors
throw new ServerException('Database error');

// Throw for maintenance mode
throw new MaintenanceException('Back in 5 minutes');
```

**All exceptions extend `IntentException`:**
- `getStatusCode()` - Returns HTTP status code
- `render()` - Returns array for JSON response

### 3.18 Security Headers Middleware

```php
use Core\SecurityHeaders;

// Apply to all admin routes
Route::group(['middleware' => SecurityHeaders::class], function () {
    Route::get('/admin', $handler);
});

// Per-route application
Route::get('/admin', $handler)->middleware(new SecurityHeaders());

// With Content Security Policy
Route::get('/app', $handler)->middleware(SecurityHeaders::withCSP("default-src 'self'"));

// Frame control
Route::get('/embed', $handler)->middleware(SecurityHeaders::allowFrames());
Route::get('/secure', $handler)->middleware(SecurityHeaders::denyFrames());

// Strict mode for sensitive pages
Route::get('/payment', $handler)->middleware(SecurityHeaders::strict());
```

**Default headers applied:**
```
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

**Strict mode adds:**
```
Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
X-Frame-Options: DENY
Referrer-Policy: no-referrer
```

---

## 4. Helpers

| Helper | Usage |
|--------|-------|
| `config($key)` | Get config value |
| `env($key)` | Get environment variable |
| `e($value)` | Escape HTML entities (XSS protection) |
| `view($name, $data)` | Render template (Twig or PHP) |
| `request()` | Get Request instance |
| `response()` | Get Response instance |
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
| `logger()` | Logging access |
| `log_message($lvl, $msg)` | Log a message |
| `upload($key)` | Get Upload instance |
| `slug($text)` | Generate URL-safe slug |
| `api_token($userId)` | Generate API token |
| `oauth_redirect($provider)` | OAuth redirect URL |
| `dd($vars)` | Dump and die |

---

## 5. CLI

```bash
php intent install         # Interactive setup (prompts for Twig)
php intent serve           # Start server (port 8080)
php intent serve 3000      # Custom port
php intent cache:clear     # Clear cache
php intent make:handler UserHandler
php intent make:middleware RateLimitMiddleware
php intent make:migration create_users_table
php intent migrate         # Run pending migrations
php intent migrate:rollback # Rollback last batch
php intent routes          # List registered routes
php intent help            # Show help
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
- 220 tests, 393 assertions
- Config, Response, Router, Validator, Pipeline, Session, Event, Cache, Registry, Upload, Paginator, Package, Log, Request, RouteGroup, ApiToken, OAuth

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
| Rate Limiting | File-based request throttling |
| XSS Protection | `e()` helper for HTML escaping |
| Security Headers | Configurable middleware |
| HTTP Exceptions | Typed exceptions with status codes |

---

## 9. File Statistics

| File | Purpose |
|------|---------|
| src/Core/App.php | Main orchestrator |
| src/Core/ApiToken.php | API token authentication |
| src/Core/Auth.php | Session authentication |
| src/Core/OAuth.php | OAuth 2.0 social login |
| src/Core/Cache.php | File caching |
| src/Core/Config.php | Configuration |
| src/Core/DB.php | Connection manager + facade |
| src/Core/QueryBuilder.php | Query building + execution |
| src/Core/Event.php | Event dispatcher |
| src/Core/Middleware.php | Interface |
| src/Core/Migration.php | Database migrations + Migrator |
| src/Core/Pipeline.php | Middleware runner |
| src/Core/RateLimiter.php | Rate limiting (file-based) |
| src/Core/Request.php | HTTP request |
| src/Core/Response.php | HTTP response |
| src/Core/Route.php | Route facade |
| src/Core/Router.php | Routing |
| src/Core/Schema.php | Auto-schema |
| src/Core/SecurityHeaders.php | Security headers middleware |
| src/Core/Session.php | Sessions |
| src/Core/Validator.php | Validation |
| src/Core/Log.php | PSR-3 compatible logging |
| src/Core/Package.php | Package auto-discovery |
| src/Core/Registry.php | Service registry |
| src/Core/Upload.php | File upload handling |
| src/Core/Paginator.php | Pagination helper |
| src/Core/Exceptions/HttpExceptions.php | Custom HTTP exceptions |

**Total: ~5000 lines of core code**

---

**End of Document**
