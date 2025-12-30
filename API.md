# Intent Framework - API Reference

> Complete API documentation with input/output types for all public methods.

---

## Table of Contents

1. [Request](#request)
2. [Response](#response)
3. [Route](#route)
4. [DB (Database)](#db-database)
5. [Validator](#validator)
6. [Session](#session)
7. [Cache](#cache)
8. [Auth](#auth)
9. [Event](#event)
10. [Registry](#registry)
11. [Log](#log)
12. [Config](#config)
13. [Helper Functions](#helper-functions)

---

## Request

**Class:** `Core\Request`

Immutable HTTP request representation. Created automatically by the framework.

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$method` | `string` | HTTP method (GET, POST, PUT, DELETE, etc.) |
| `$uri` | `string` | Full request URI with query string |
| `$path` | `string` | Request path without query string |
| `$query` | `array` | Query parameters ($_GET) |
| `$post` | `array` | POST parameters ($_POST) |
| `$headers` | `array` | HTTP headers (lowercase keys) |
| `$body` | `?string` | Raw request body |

### Methods

#### `get(string $key, mixed $default = null): mixed`

Get a query parameter.

```php
$page = $request->get('page', 1);
$search = $request->get('q');
```

#### `post(string $key, mixed $default = null): mixed`

Get a POST parameter.

```php
$name = $request->post('name');
$email = $request->post('email', 'default@example.com');
```

#### `header(string $key, mixed $default = null): mixed`

Get a header value (case-insensitive).

```php
$token = $request->header('authorization');
$type = $request->header('content-type', 'text/html');
```

#### `json(): ?array`

Get JSON decoded body. Returns `null` if body is empty or invalid JSON.

```php
$data = $request->json();
// ['name' => 'John', 'email' => 'john@example.com']
```

#### `isAjax(): bool`

Check if request is AJAX (XMLHttpRequest).

```php
if ($request->isAjax()) {
    return $response->json(['status' => 'ok']);
}
```

#### `wantsJson(): bool`

Check if client expects JSON response (Accept header contains application/json).

```php
if ($request->wantsJson()) {
    return $response->json($data);
}
return $response->html($view);
```

---

## Response

**Class:** `Core\Response`

Fluent HTTP response builder.

### Methods

#### `status(int $code): self`

Set HTTP status code.

```php
$response->status(201);
$response->status(404);
```

#### `header(string $name, string $value): self`

Set a response header.

```php
$response->header('X-Custom', 'value');
$response->header('Cache-Control', 'no-cache');
```

#### `body(string $content): self`

Set raw response body.

```php
$response->body('<h1>Hello</h1>');
```

#### `html(string $content, int $status = 200): self`

Send HTML response with proper Content-Type.

```php
return $response->html('<h1>Welcome</h1>');
return $response->html($errorPage, 404);
```

#### `json(mixed $data, int $status = 200): self`

Send JSON response.

```php
return $response->json(['user' => $user]);
return $response->json(['error' => 'Not found'], 404);
```

#### `text(string $content, int $status = 200): self`

Send plain text response.

```php
return $response->text('OK');
```

#### `redirect(string $url, int $status = 302): self`

Redirect to URL.

```php
return $response->redirect('/login');
return $response->redirect('/new-page', 301); // Permanent redirect
```

#### `send(): never`

Send response and exit. Called automatically by the framework.

```php
$response->json(['ok' => true])->send();
```

#### `getStatus(): int`

Get current status code.

#### `getBody(): string`

Get current body content.

---

## Route

**Class:** `Core\Route`

Static facade for route registration.

### Methods

#### `get(string $path, callable $handler): Router`

Register a GET route.

```php
Route::get('/users', function(Request $request, Response $response) {
    return $response->json(['users' => []]);
});
```

#### `post(string $path, callable $handler): Router`

Register a POST route.

```php
Route::post('/users', function(Request $request, Response $response) {
    $data = $request->json();
    return $response->json(['id' => 1], 201);
});
```

#### `put(string $path, callable $handler): Router`

Register a PUT route.

#### `delete(string $path, callable $handler): Router`

Register a DELETE route.

#### `any(string $path, callable $handler): Router`

Register route for any HTTP method.

#### `add(string $method, string $path, callable $handler): Router`

Register route for specific method.

```php
Route::add('PATCH', '/users/{id}', $handler);
```

#### `group(array $attributes, callable $callback): void`

Create route group with shared attributes.

**Parameters:**
- `$attributes['prefix']` - URL prefix for all routes
- `$attributes['middleware']` - Middleware class(es) to apply

```php
Route::group(['prefix' => '/api', 'middleware' => AuthMiddleware::class], function() {
    Route::get('/users', $handler);    // /api/users
    Route::get('/posts', $handler);    // /api/posts
});
```

#### `prefix(string $prefix, callable $callback): void`

Shorthand for prefix-only group.

```php
Route::prefix('/admin', function() {
    Route::get('/dashboard', $handler);
});
```

#### `middleware(array|string $middleware, callable $callback): void`

Shorthand for middleware-only group.

```php
Route::middleware(AuthMiddleware::class, function() {
    Route::get('/profile', $handler);
});
```

---

## DB (Database)

**Classes:** `Core\DB` (facade), `Core\QueryBuilder` (query building)

Database layer with separated concerns: `DB` manages connections, `QueryBuilder` builds and executes queries.

### Architecture

The database layer consists of two classes:

- **DB**: Connection manager and static facade
- **QueryBuilder**: Query building and execution

```php
// DB creates QueryBuilder instances
$query = DB::table('users');  // Returns QueryBuilder instance
$users = $query->where('active', 1)->get();

// Fluent chaining (most common usage)
$users = DB::table('users')->where('active', 1)->get();
```

**Benefits of Separation:**
- Single Responsibility Principle
- Better testability
- Cleaner code organization
- Industry standard pattern
- More extensible

### Static Methods

#### `table(string $table): self`

Start a query on a table.

```php
$users = DB::table('users')->get();
```

#### `raw(string $sql, array $bindings = []): array`

Execute raw SQL query.

```php
$results = DB::raw('SELECT * FROM users WHERE id = ?', [1]);
```

#### `connection(): PDO`

Get the PDO connection instance.

### Query Methods

#### `select(string|array $columns = ['*']): self`

Select specific columns.

```php
DB::table('users')->select(['id', 'name'])->get();
DB::table('users')->select('id, name')->get();
```

#### `where(string $column, mixed $operatorOrValue, mixed $value = null): self`

Add WHERE clause.

```php
DB::table('users')->where('id', 1)->first();
DB::table('users')->where('age', '>=', 18)->get();
DB::table('posts')->where('status', 'published')->get();
```

#### `whereIn(string $column, array $values): self`

Add WHERE IN clause.

```php
DB::table('users')->whereIn('id', [1, 2, 3])->get();
```

#### `whereNull(string $column): self`

Add WHERE NULL clause.

```php
DB::table('users')->whereNull('deleted_at')->get();
```

#### `whereNotNull(string $column): self`

Add WHERE NOT NULL clause.

```php
DB::table('users')->whereNotNull('email_verified_at')->get();
```

#### `orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self`

Add OR WHERE clause. Allows complex AND/OR combinations.

```php
// Find posts that are published OR featured
DB::table('posts')
    ->where('status', 'published')
    ->orWhere('featured', 1)
    ->get();

// Complex conditions: (role = 'admin' AND active = 1) OR role = 'moderator'
DB::table('users')
    ->where('role', 'admin')
    ->where('active', 1)
    ->orWhere('role', 'moderator')
    ->get();
```

#### `orWhereIn(string $column, array $values): self`

Add OR WHERE IN clause.

```php
DB::table('posts')
    ->where('author_id', 1)
    ->orWhereIn('status', ['draft', 'pending'])
    ->get();
```

#### `orWhereNull(string $column): self`

Add OR WHERE NULL clause.

```php
DB::table('posts')
    ->where('published', 1)
    ->orWhereNull('deleted_at')
    ->get();
```

#### `orWhereNotNull(string $column): self`

Add OR WHERE NOT NULL clause.

```php
DB::table('users')
    ->where('role', 'guest')
    ->orWhereNotNull('subscription_id')
    ->get();
```

#### `orderBy(string $column, string $direction = 'ASC'): self`

Add ORDER BY clause.

```php
DB::table('posts')->orderBy('created_at', 'DESC')->get();
```

#### `limit(int $limit): self`

Limit results.

```php
DB::table('users')->limit(10)->get();
```

#### `offset(int $offset): self`

Offset results.

```php
DB::table('users')->limit(10)->offset(20)->get();
```

### Retrieval Methods

#### `get(): array`

Get all matching rows.

```php
$users = DB::table('users')->where('active', 1)->get();
// [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']]
```

#### `first(): ?array`

Get first matching row.

```php
$user = DB::table('users')->where('email', 'john@example.com')->first();
// ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'] or null
```

#### `find(int|string $id, string $primaryKey = 'id'): ?array`

Find by primary key.

```php
$user = DB::table('users')->find(1);
$post = DB::table('posts')->find('abc-123', 'slug');
```

#### `count(): int`

Count matching rows.

```php
$total = DB::table('users')->where('active', 1)->count();
```

#### `exists(): bool`

Check if any rows exist.

```php
if (DB::table('users')->where('email', $email)->exists()) {
    // Email already taken
}
```

### Mutation Methods

#### `insert(array $data): int|string`

Insert a row. Returns last insert ID.

```php
$id = DB::table('users')->insert([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

#### `insertMany(array $rows): void`

Insert multiple rows.

```php
DB::table('users')->insertMany([
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
]);
```

#### `update(array $data): int`

Update matching rows. Returns affected row count.

```php
$affected = DB::table('users')
    ->where('id', 1)
    ->update(['name' => 'John Doe']);
```

#### `delete(): int`

Delete matching rows. Returns deleted row count.

```php
$deleted = DB::table('users')->where('id', 1)->delete();
```

### Type Casting

The query builder automatically casts values to appropriate database types for better compatibility and correctness.

#### DateTime Objects

`DateTimeInterface` objects are automatically converted to SQL datetime format (`Y-m-d H:i:s`).

```php
use DateTime;
use DateTimeImmutable;

DB::table('posts')->insert([
    'title' => 'My Post',
    'published_at' => new DateTime('2024-01-01 12:00:00'),
    'updated_at' => new DateTimeImmutable('now')
]);
// Automatically converts to: '2024-01-01 12:00:00' and current timestamp
```

#### Booleans

Boolean values are converted to integers (1/0) with proper `PDO::PARAM_INT` typing.

```php
DB::table('users')->insert([
    'name' => 'John',
    'is_active' => true,      // Converts to 1 (integer)
    'is_verified' => false    // Converts to 0 (integer)
]);

DB::table('posts')->where('published', true)->get();
// WHERE published = 1
```

#### Null Values

Null values are properly typed as `PDO::PARAM_NULL`.

```php
DB::table('posts')->update([
    'deleted_at' => null  // Properly typed as NULL
]);
```

#### Integers and Floats

Integers use `PDO::PARAM_INT`, floats use `PDO::PARAM_STR` (PDO limitation).

```php
DB::table('products')->insert([
    'quantity' => 10,        // PDO::PARAM_INT
    'price' => 19.99        // PDO::PARAM_STR (converted to string)
]);
```

### Database Compatibility

The framework supports multiple database drivers with automatic compatibility handling.

#### Supported Databases

- **MySQL** (default) - Full support with backtick identifier escaping
- **PostgreSQL** - Full support with double-quote identifier escaping  
- **SQLite** - Full support with double-quote identifier escaping

#### Configuration Examples

**MySQL**:
```php
// config/app.php
'db' => [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => 3306,
    'name' => 'my_database',
    'user' => 'root',
    'pass' => 'password'
]
```

**SQLite**:
```php
'db' => [
    'driver' => 'sqlite',
    'name' => '/path/to/database.db',  // Full path to SQLite file
    // host, port, user, pass not needed for SQLite
]
```

**PostgreSQL**:
```php
'db' => [
    'driver' => 'pgsql',
    'host' => 'localhost',
    'port' => 5432,
    'name' => 'my_database',
    'user' => 'postgres',
    'pass' => 'password'
]
```

#### Identifier Escaping

All table and column names are automatically escaped based on the database driver:

- **MySQL**: Uses backticks `` `table_name` ``
- **PostgreSQL/SQLite**: Uses double quotes `"table_name"`

This allows safe use of reserved keywords:

```php
// Safe to use reserved keywords
DB::table('order')->where('type', 'sale')->get();
// MySQL: SELECT * FROM `order` WHERE `type` = ?
// PostgreSQL: SELECT * FROM "order" WHERE "type" = ?

// Qualified identifiers are also escaped
DB::table('users')
    ->select(['users.id', 'users.name', 'orders.total'])
    ->get();
// MySQL: SELECT `users`.`id`, `users`.`name`, `orders`.`total` FROM `users`
```

> **Note**: Identifier escaping is automatic and transparent. You don't need to manually escape table or column names.

---

## Validator

**Class:** `Core\Validator`

Input validation with multiple rules.

### Constructor

```php
$validator = new Validator(
    array $data,           // Data to validate
    array $rules,          // Validation rules
    array $messages = []   // Custom error messages (optional)
);
```

### Static Factory

#### `make(array $data, array $rules, array $messages = []): self`

```php
$validator = Validator::make($request->json(), [
    'name' => 'required|min:3|max:255',
    'email' => 'required|email',
    'age' => 'integer|min:18'
]);
```

### Methods

#### `fails(): bool`

Check if validation failed.

```php
if ($validator->fails()) {
    return $response->json(['errors' => $validator->errors()], 422);
}
```

#### `passes(): bool`

Check if validation passed.

#### `errors(): array`

Get all validation errors.

```php
$errors = $validator->errors();
// ['email' => ['The email field must be a valid email address']]
```

#### `validated(): array`

Get only validated data (fields that passed validation).

```php
$data = $validator->validated();
// Only includes validated fields
```

#### `firstError(): ?string`

Get first error message.

```php
$error = $validator->firstError();
// "The name field is required"
```

### Available Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must be present and not empty | `'name' => 'required'` |
| `email` | Must be valid email | `'email' => 'email'` |
| `url` | Must be valid URL | `'website' => 'url'` |
| `integer` | Must be integer | `'age' => 'integer'` |
| `numeric` | Must be numeric | `'price' => 'numeric'` |
| `boolean` | Must be boolean | `'active' => 'boolean'` |
| `string` | Must be string | `'name' => 'string'` |
| `array` | Must be array | `'tags' => 'array'` |
| `min:n` | Minimum length/value | `'password' => 'min:8'` |
| `max:n` | Maximum length/value | `'bio' => 'max:500'` |
| `between:min,max` | Value between range | `'age' => 'between:18,99'` |
| `in:a,b,c` | Must be in list | `'status' => 'in:draft,published'` |
| `not_in:a,b,c` | Must not be in list | `'role' => 'not_in:admin'` |
| `regex:/pattern/` | Must match regex | `'code' => 'regex:/^[A-Z]{3}$/'` |
| `confirmed` | Must have matching `_confirmation` field | `'password' => 'confirmed'` |
| `alpha` | Only letters | `'name' => 'alpha'` |
| `alpha_num` | Only letters and numbers | `'username' => 'alpha_num'` |
| `nullable` | Allow null values | `'bio' => 'nullable|max:500'` |

### Custom Messages

```php
$validator = Validator::make($data, $rules, [
    'email.required' => 'We need your email address',
    'email.email' => 'Please enter a valid email',
    'age.min' => 'You must be at least :min years old'
]);
```

---

## Session

**Class:** `Core\Session`

Session management with flash messages.

### Methods

#### `start(): void`

Start the session. Called automatically when needed.

#### `get(string $key, mixed $default = null): mixed`

Get a session value.

```php
$userId = Session::get('user_id');
$name = Session::get('name', 'Guest');
```

#### `set(string $key, mixed $value): void`

Set a session value.

```php
Session::set('user_id', 123);
```

#### `has(string $key): bool`

Check if key exists.

```php
if (Session::has('user_id')) {
    // User is logged in
}
```

#### `forget(string $key): void`

Remove a session value.

```php
Session::forget('cart');
```

#### `all(): array`

Get all session data.

#### `clear(): void`

Clear all session data.

#### `destroy(): void`

Destroy session completely.

#### `regenerate(): void`

Regenerate session ID (for security).

```php
Session::regenerate(); // After login
```

#### `id(): string`

Get current session ID.

### Flash Messages

#### `flash(string $key, mixed $value): void`

Set flash message (available on next request only).

```php
Session::flash('success', 'Your changes have been saved!');
```

#### `getFlash(string $key, mixed $default = null): mixed`

Get a flash message.

```php
$message = Session::getFlash('success');
```

#### `getFlashes(): array`

Get all flash messages.

#### `hasFlash(string $key): bool`

Check if flash message exists.

### Convenience Methods

#### `push(string $key, mixed $value): void`

Push value onto array session value.

```php
Session::push('notifications', 'New message');
```

#### `increment(string $key, int $amount = 1): int`

Increment numeric session value.

```php
Session::increment('page_views');
```

#### `decrement(string $key, int $amount = 1): int`

Decrement numeric session value.

---

## Cache

**Class:** `Core\Cache`

File-based caching.

### Methods

#### `put(string $key, mixed $value, int $ttl = 0): void`

Store a value. TTL in seconds (0 = forever).

```php
Cache::put('user:1', $user, 3600);  // 1 hour
Cache::put('settings', $config, 0); // Forever
```

#### `get(string $key, mixed $default = null): mixed`

Retrieve a value.

```php
$user = Cache::get('user:1');
$settings = Cache::get('settings', []);
```

#### `has(string $key): bool`

Check if key exists and is not expired.

```php
if (Cache::has('user:1')) {
    return Cache::get('user:1');
}
```

#### `forget(string $key): void`

Remove a cached value.

```php
Cache::forget('user:1');
```

#### `flush(): void`

Clear all cached values.

```php
Cache::flush();
```

#### `remember(string $key, int $ttl, callable $callback): mixed`

Get or compute value.

```php
$users = Cache::remember('all_users', 3600, function() {
    return DB::table('users')->get();
});
```

#### `forever(string $key, mixed $value): void`

Store forever (no expiration).

```php
Cache::forever('app_version', '1.0.0');
```

#### `increment(string $key, int $amount = 1): int`

Increment cached value.

```php
Cache::increment('visits');
Cache::increment('downloads', 5);
```

#### `decrement(string $key, int $amount = 1): int`

Decrement cached value.

---

## Auth

**Class:** `Core\Auth`

Session-based authentication.

### Methods

#### `configure(string $table, string $usernameField, string $passwordField): void`

Configure auth settings.

```php
Auth::configure('users', 'email', 'password');
```

#### `attempt(array $credentials): bool`

Attempt login with credentials.

```php
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    return redirect('/dashboard');
}
return redirect('/login')->withError('Invalid credentials');
```

#### `login(array $user): void`

Log in user directly (without password check).

```php
Auth::login($user);
```

#### `logout(): void`

Log out current user.

```php
Auth::logout();
return redirect('/');
```

#### `check(): bool`

Check if user is logged in.

```php
if (Auth::check()) {
    // User is authenticated
}
```

#### `guest(): bool`

Check if user is guest (not logged in).

```php
if (Auth::guest()) {
    return redirect('/login');
}
```

#### `user(): ?array`

Get current authenticated user.

```php
$user = Auth::user();
// ['id' => 1, 'name' => 'John', 'email' => 'john@example.com']
```

#### `id(): ?int`

Get current user's ID.

```php
$userId = Auth::id();
```

### Password Helpers

#### `hash(string $password): string`

Hash a password (bcrypt).

```php
$hash = Auth::hash('secret123');
```

#### `verify(string $password, string $hash): bool`

Verify password against hash.

```php
if (Auth::verify($password, $user['password'])) {
    // Password matches
}
```

#### `needsRehash(string $hash): bool`

Check if password needs rehashing.

### User Management

#### `createUser(array $data): int`

Create user with hashed password.

```php
$id = Auth::createUser([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'secret123'
]);
```

#### `findById(int $id): ?array`

Find user by ID (password field removed).

#### `findByUsername(string $username): ?array`

Find user by email/username (password field removed).

---

## Event

**Class:** `Core\Event`

Simple event dispatcher.

### Methods

#### `listen(string $event, callable $listener): void`

Register event listener.

```php
Event::listen('user.created', function($user) {
    sendWelcomeEmail($user);
});

Event::listen('user.created', function($user) {
    Log::info('User created', ['id' => $user['id']]);
});
```

#### `dispatch(string $event, mixed ...$data): array`

Dispatch event to all listeners. Returns array of results.

```php
Event::dispatch('user.created', $user);
Event::dispatch('order.placed', $order, $items);
```

#### `until(string $event, mixed ...$data): mixed`

Dispatch and halt on first non-null result.

```php
$result = Event::until('user.validating', $user);
if ($result === false) {
    // Validation failed
}
```

#### `hasListeners(string $event): bool`

Check if event has listeners.

#### `getListeners(string $event): array`

Get all listeners for event.

#### `forget(string $event): void`

Remove all listeners for event.

#### `flush(): void`

Remove all listeners.

---

## Registry

**Class:** `Core\Registry`

Minimal service registry for testability. Bind, singleton, resolve.

### Methods

#### `bind(string $id, callable $factory, bool $singleton = false): void`

Bind a factory to a key. Called fresh each resolution.

```php
Registry::bind('cache', fn() => new FileCache());
$cache = Registry::make('cache');
```

#### `singleton(string $id, callable $factory): void`

Bind as singleton (resolved once, cached).

```php
Registry::singleton('db', fn() => new PDO($dsn));
$db = Registry::make('db');  // Same instance every time
```

#### `instance(string $id, object $instance): void`

Bind an existing instance directly.

```php
$mockCache = new ArrayCache();
Registry::instance('cache', $mockCache);
```

#### `make(string $id): mixed`

Resolve a binding.

```php
$cache = Registry::make('cache');
```

#### `has(string $id): bool`

Check if binding exists.

```php
if (Registry::has('cache')) {
    $cache = Registry::make('cache');
}
```

#### `forget(string $id): void`

Remove a binding.

#### `flush(): void`

Clear all bindings (for testing).

```php
// In test tearDown
Registry::flush();
```

#### `keys(): array`

Get all registered binding keys.

### Testing Example

```php
class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Swap file cache with array cache for testing
        Registry::bind('cache', fn() => new ArrayCache());
    }

    protected function tearDown(): void
    {
        Registry::flush();
    }
}
```

---

## Log

**Class:** `Core\Log`

File-based logging to `storage/logs/`.

### Methods

#### `debug(string $message, array $context = []): void`

Log debug message.

```php
Log::debug('Processing started', ['items' => count($items)]);
```

#### `info(string $message, array $context = []): void`

Log info message.

```php
Log::info('User logged in', ['user_id' => 123]);
```

#### `warning(string $message, array $context = []): void`

Log warning message.

```php
Log::warning('Low disk space', ['available' => '500MB']);
```

#### `error(string $message, array $context = []): void`

Log error message.

```php
Log::error('Payment failed', ['order_id' => 456, 'error' => $e->getMessage()]);
```

#### `critical(string $message, array $context = []): void`

Log critical message.

```php
Log::critical('Database connection lost');
```

#### `log(string $level, string $message, array $context = []): void`

Log with specified level.

```php
Log::log('info', 'Custom log', ['key' => 'value']);
```

#### `read(?string $date = null): string`

Read log file contents.

```php
$today = Log::read();
$yesterday = Log::read('2024-01-15');
```

#### `clear(): void`

Clear all log files.

#### `dates(): array`

Get available log dates.

```php
$dates = Log::dates();
// ['2024-01-16', '2024-01-15', '2024-01-14']
```

---

## Config

**Class:** `Core\Config`

Configuration management.

### Methods

#### `load(string $path): void`

Load config from PHP file.

```php
Config::load(BASE_PATH . '/config.php');
```

#### `get(string $key, mixed $default = null): mixed`

Get config value (supports dot notation).

```php
$debug = Config::get('app.debug', false);
$driver = Config::get('database.driver');
```

#### `set(string $key, mixed $value): void`

Set config value.

```php
Config::set('app.debug', true);
```

#### `all(): array`

Get all config values.

---

## Helper Functions

Global helper functions available everywhere.

### Request/Response

| Function | Returns | Description |
|----------|---------|-------------|
| `request()` | `Request` | Get current request |
| `response()` | `Response` | Get new response |
| `json(mixed $data, int $status = 200)` | `Response` | JSON response |
| `redirect(string $url, int $status = 302)` | `Response` | Redirect response |
| `view(string $name, array $data = [])` | `string` | Render view |

### Validation

| Function | Returns | Description |
|----------|---------|-------------|
| `validate(array $data, array $rules)` | `Validator` | Create validator |

### Session

| Function | Returns | Description |
|----------|---------|-------------|
| `session(string $key = null, mixed $value = null)` | `mixed` | Get/set session |
| `flash(string $key, mixed $value = null)` | `mixed` | Get/set flash |

### Auth

| Function | Returns | Description |
|----------|---------|-------------|
| `auth()` | `array` | Auth helper methods |
| `user()` | `?array` | Current user |

### Events

| Function | Returns | Description |
|----------|---------|-------------|
| `event(string $name, mixed ...$data)` | `array` | Dispatch event |

### Cache

| Function | Returns | Description |
|----------|---------|-------------|
| `cache(string $key = null, mixed $value = null, int $ttl = 0)` | `mixed` | Get/set cache |

### Logging

| Function | Returns | Description |
|----------|---------|-------------|
| `logger(?string $level = null, ?string $message = null, array $context = [])` | `object` | Get logger or log |
| `log_message(string $level, string $message, array $context = [])` | `void` | Log message |

### CSRF

| Function | Returns | Description |
|----------|---------|-------------|
| `csrf_token()` | `string` | Get CSRF token |
| `csrf_field()` | `string` | Hidden input field |

### Environment

| Function | Returns | Description |
|----------|---------|-------------|
| `config(string $key, mixed $default = null)` | `mixed` | Get config |
| `env(string $key, mixed $default = null)` | `mixed` | Get env variable |

### Debug

| Function | Returns | Description |
|----------|---------|-------------|
| `dd(mixed ...$vars)` | `never` | Dump and die |

---

## Middleware

**Interface:** `Core\Middleware`

All middleware must implement this interface.

```php
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
```

### Creating Middleware

```php
<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}
```

### Applying Middleware

```php
// Single route
Route::get('/dashboard', $handler)->middleware(AuthMiddleware::class);

// Route group
Route::middleware(AuthMiddleware::class, function() {
    Route::get('/profile', $handler);
    Route::get('/settings', $handler);
});
```

---

## Built-in Middleware

### AuthMiddleware

Requires authenticated user.

```php
Route::get('/dashboard', $handler)->middleware(AuthMiddleware::class);
```

### GuestMiddleware

Requires guest (not logged in).

```php
Route::get('/login', $handler)->middleware(GuestMiddleware::class);
```

### CsrfMiddleware

Validates CSRF tokens on POST/PUT/PATCH/DELETE.

```php
Route::middleware(CsrfMiddleware::class, function() {
    Route::post('/update', $handler);
});
```

### RateLimitMiddleware

Rate limits requests per IP.

```php
// Default: 60 requests per minute
Route::post('/api/login', $handler)->middleware(RateLimitMiddleware::class);

// Custom: 5 requests per 60 seconds
Route::post('/api/login', $handler)->middleware(new RateLimitMiddleware(5, 60));
```

---

**End of API Reference**
