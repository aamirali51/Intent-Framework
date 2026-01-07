# Intent Framework – AI Usage Guide

> **Purpose**: This document is written **for AI agents** (Cursor, Copilot, Antigravity, LLMs).  
> If you are an AI generating code for this repository, **follow this document strictly**.

---

## 1. Framework Identity (Very Important)

- **Name**: Intent Framework
- **Language**: PHP 8.2+
- **Type**: API-first micro-framework
- **Philosophy**: Explicit over magic, zero boilerplate, predictable patterns

### This framework is NOT:
- ❌ Laravel
- ❌ Symfony
- ❌ MVC-heavy
- ❌ ORM-based
- ❌ Controller-driven

### This framework IS:
- ✅ Route + Handler based
- ✅ API-first
- ✅ Stateless-friendly
- ✅ AI-native by design
- ✅ Minimal abstraction

**Do NOT assume Laravel conventions.**

---

## 2. Application Entry Point

- Entry file: `public/index.php`
- Bootstraps: `Core\App`
- Routes loaded from:
  - `config/routes.php`
  - `app/Api/*` (file-based routes if enabled)

The request lifecycle is:
```
HTTP Request
 → Router
 → Middleware Pipeline
 → Handler
 → Response
```

---

## 3. Route & Handler Pattern (Golden Rule)

### Canonical handler signature:
```php
function ($request, $response, $params = []) {
    // logic
    return $response;
}
```

- `$request` is immutable (`Core\Request`)
- `$response` is fluent (`Core\Response`)
- `$params` contains route parameters (e.g. `{id}`)

### Example:
```php
Route::get('/users/{id}', function ($req, $res, $params) {
    return $res->json(['id' => $params['id']]);
});
```

---

## 4. Creating a Feature (Correct Order)

When asked to "build a feature", ALWAYS follow this order:

1. Define route
2. Attach middleware (auth, rate limit, csrf)
3. Validate input
4. Perform DB or service logic
5. Return JSON or redirect

Never skip validation or middleware.

---

## 5. Validation (Mandatory)

All user input **MUST be validated**.

```php
$v = validate($request->json(), [
    'title' => 'required|string|min:3',
    'content' => 'required|string'
]);

if ($v->fails()) {
    return $response->json(['errors' => $v->errors()], 422);
}
```

- Use `422 Unprocessable Entity` for validation errors
- Use `$v->validated()` for safe data

---

## 6. Database Rules

### Allowed:
```php
DB::table('users')->get();
DB::table('posts')->where('id', 1)->first();
DB::table('posts')->insert($data);
```

### Forbidden:
- ❌ Raw SQL in handlers
- ❌ String-concatenated queries
- ❌ ORM-style models

Raw SQL is allowed **ONLY in migrations**.

---

## 7. Authentication & Authorization

### Authentication methods:
- Session-based (web)
- Bearer token (API)

### Apply authentication:
```php
Route::get('/api/me', $handler)
    ->middleware(AuthMiddleware::class);
```

### Access user:
```php
$user = auth()->user();
```

Never trust user input for identity.

---

## 8. API Tokens (AI & Automation Friendly)

- Tokens are hashed in DB
- Token may have TTL
- Token may have a name

```php
$token = api_token($userId, 'ai-agent', 86400);
```

Use API tokens for:
- AI agents
- Automation
- External integrations

---

## 9. Error Handling (Strict)

Use typed HTTP exceptions when appropriate:

```php
throw new NotFoundException('Post not found');
throw new UnauthorizedException('Login required');
throw new ValidationException($errors);
```

Do NOT return random error arrays.

---

## 10. Security Defaults

- CSRF required for POST (except pure APIs)
- Rate limit sensitive endpoints
- Use `SecurityHeaders` middleware for admin

```php
Route::post('/login', $handler)
    ->middleware(RateLimiter::perMinute(5));
```

---

## 11. What NOT to Generate (Critical)

AI MUST NOT:
- Generate controllers
- Use service providers
- Add dependency injection containers
- Invent framework helpers
- Assume Blade or Twig
- Use Eloquent or Doctrine

If unsure, choose **explicit and simple code**.

---

## 12. Mental Model Summary (For AI)

> Intent Framework = Routes + Middleware + Handlers + Explicit APIs

If a solution looks magical, implicit, or "Laravel-like" — it is WRONG.

---

## 13. When in Doubt

- Prefer clarity over abstraction
- Prefer functions over classes
- Prefer explicit middleware
- Prefer predictable behavior

This framework is designed so both **humans and AI can reason about it safely**.

