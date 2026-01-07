<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-8892BF?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Composer-PSR--4-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer">
  <img src="https://img.shields.io/badge/Tests-220%20Passing-brightgreen?style=for-the-badge" alt="Tests">
  <img src="https://img.shields.io/badge/PHPStan-Level%209-4ade80?style=for-the-badge" alt="PHPStan Level 9">
  <img src="https://img.shields.io/badge/Version-0.8.1-blue?style=for-the-badge" alt="Version">
</p>

<p align="center">
  <a href="https://packagist.org/packages/intent/framework"><img src="https://img.shields.io/packagist/v/intent/framework.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/intent/framework"><img src="https://img.shields.io/packagist/dt/intent/framework.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://github.com/aamirali51/Intent-Framework/actions"><img src="https://github.com/aamirali51/Intent-Framework/workflows/CI/badge.svg" alt="CI Status"></a>

</p>

<p align="center">
  <img src="https://img.shields.io/badge/Built_by-non_expert-red.svg" alt="Built by non-expert">
  <img src="https://img.shields.io/badge/Time-<1_weekend-green.svg" alt="Time taken">
  <img src="https://img.shields.io/badge/AI-powered-blue.svg" alt="AI powered">
</p>

<h1 align="center">Intent Framework</h1>

<p align="center">
  <strong>A zero-boilerplate, AI-native, explicitly designed PHP micro-framework</strong>
</p>

<p align="center">
  ~3,000 lines of core code. No magic. No facades. No providers.<br>
  Just PHP that reads like English.
</p>

<p align="center">
  <em>Yes, most of this was written by AI. Yes, I'm not a PHP expert. No, I don't care.</em>
</p>

> ⚠️ **Early Development** — Intent is functional and tested (220 tests, 393 assertions!), but it's still young. Perfect for side projects, learning, and experimentation. Not yet battle-tested in production. Bugs? Probably. PRs? Welcome.

---

## ⚡ Quick Look

```php
// Route with middleware
Route::get('/dashboard', fn($req, $res) => $res->json([
    'user' => user(),
    'stats' => cache('dashboard_stats'),
]))->middleware(AuthMiddleware::class);

// Validation
$v = validate($request->json(), [
    'email' => 'required|email',
    'password' => 'required|min:8',
]);

if ($v->fails()) {
    return $response->json(['errors' => $v->errors()], 422);
}

// Database (v0.8+ helper syntax)
$users = db()->table('users')->get();

// Cache with remember pattern
$stats = cache()->remember('stats', 3600, fn() => 
    db()->table('stats')->first()
);

// Events
Event::listen('user.created', fn($user) => sendWelcomeEmail($user));
event('user.created', $newUser);
```

---

## 🤔 Why Intent Exists

I was tired of:

- **Laravel's 100k+ lines** and magic I couldn't trace
- **Facades and containers** that hide what's actually happening  
- **Convention over configuration** that confused AI assistants
- **Frameworks that require a PhD** to understand the request lifecycle

I wanted something:

- **Simple enough for AI to generate correct code** consistently
- **Explicit enough that I could read any file** and understand it
- **Small enough to learn in one sitting** (~3k lines total)
- **Powerful enough to build real apps** without reaching for Laravel

Intent is that framework.

---

## 🔥 Addressing the Roasts

This project was posted on r/PHP and got absolutely flamed. Let me own that:

| The Criticism | The Reality Now |
|---------------|-----------------|
| "AI-generated slop" | Yes, AI-assisted — and it's clean, tested, and consistent |
| "No tests" | **220 tests, 393 assertions** via PHPUnit |
| "Incomplete" | v0.8.1 has middleware, auth, sessions, events, cache, CLI, registry proxies |
| "No Composer" | Full `composer.json`, PSR-4 autoloading, proper `vendor/` |
| "Bad structure" | `public/` separation, `src/Core/` for framework, `app/` for user code |
| "Just use Laravel" | Sure — if you want facades and service containers. This is the opposite. |

> The whole point of Intent is that it's **readable, predictable, and AI-friendly**.  
> Whether a human or an AI writes the code, it should be obvious what it does.

---

## 🛡️ Quality Gates

| Tool | Level | Status |
|------|-------|--------|
| **PHPStan** | Level 9 | ✅ Passing |

| **PHPUnit** | 220 tests | ✅ Passing |
| **GitHub Actions** | CI/CD | ✅ Automated |

> All quality checks run automatically on every push and pull request.

---

## ✨ Key Features

| Feature | Description |
|---------|-------------|
| **Immutable Request** | Readonly properties, no mutation bugs |
| **Fluent Response** | `$res->status(201)->json($data)` |
| **Middleware Pipeline** | Per-route, no global stack magic |
| **Session + Flash** | `session('key')`, `flash('success', 'Saved!')` |
| **Auth** | `auth()->attempt()`, `user()`, password hashing |
| **Events** | Simple dispatcher: `event('user.created', $user)` |
| **Cache** | File-based: `Cache::remember('key', 3600, $fn)` |
| **Validator** | 18 rules: `'email' => 'required|email|max:255'` |
| **Query Builder** | OR conditions, type casting, multi-DB support |
| **Dev Schema** | Auto-creates tables in dev mode (disabled in prod) |
| **Secure File Routes** | Outside `public/`, in `app/Api/` |
| **CLI Tool** | `php intent serve`, `php intent make:handler` |
| **Registry Proxies** | Type-safe service access with PHPStan Level 9 support |

---

## 🔄 Service Access Pattern (v0.8+)

All services are accessed via **registry-backed helpers** for consistency and testability:

```php
// ✅ Canonical pattern (v0.8+)
db()->table('users')->where('id', 1)->first();
auth()->user();
cache('key', $value, 3600);
session('user_id');
logger()->info('Message');

// ❌ Deprecated (still works, avoid in new code)
DB::table('users')->get();  // Use db() instead
Cache::put('key', $value);  // Use cache() instead
```

> Static facades will be removed in v2.0. Use helpers for new code.

---

## 📦 Installation

**Option 1: Composer (Recommended)**
```bash
composer create-project intent/framework my-app
cd my-app
php intent serve
```

**Option 2: Clone**
```bash
git clone https://github.com/aamirali51/Intent-Framework.git my-app
cd my-app
composer install
```

**Option 3: Using as Library**

If you want to use Intent Framework as a dependency in an existing project:

```bash
composer require intent/framework
```

**Important Notes:**
1. **Define `BASE_PATH` first** — Must be set before anything else
2. **Let `Core\App` handle initialization** — Don't load routes manually before App is constructed
3. **Config uses flat dot-notation keys** — e.g., `'app.name'`, not nested arrays

**Example bootstrap:**
```php
<?php
declare(strict_types=1);

// Define BASE_PATH first (required)
define('BASE_PATH', __DIR__);

// Load autoloader
require BASE_PATH . '/vendor/autoload.php';

// App handles config/routes internally via Route::setRouter()
$app = new Core\App();
$app->run();
```

> ⚠️ The App constructor initializes the Router internally via `Route::setRouter()`. If you load routes before constructing App, the router won't be initialized.

---

## 🚀 Quick Start

```bash
# Start the development server
php intent serve

# Or manually
php -S localhost:8080 -t public
```

Visit **http://localhost:8080** — you should see the welcome page.

---

## 🛠️ CLI Commands

```bash
php intent --help            # Show all available commands
php intent serve             # Start dev server (port 8080)
php intent serve 3000        # Custom port
php intent cache:clear       # Clear all cached data
php intent make:handler      # Create a handler class
php intent make:middleware   # Create a middleware class
php intent routes            # List registered routes
```

---

## 📚 Documentation

- **[API Reference](./API.md)** — Complete API with input/output types for every method
- **[Architecture](./ARCHITECTURE.md)** — Technical docs, directory structure, and internals

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Or directly
vendor/bin/phpunit
```

**Current coverage:** 220 tests, 393 assertions

---

## 💡 Philosophy

```
┌─────────────────────────────────────────────────┐
│  Zero Boilerplate                               │
│  ───────────────                                │
│  No service containers. No providers.           │
│  No facades. No annotations.                    │
├─────────────────────────────────────────────────┤
│  Explicit Over Magic                            │
│  ──────────────────                             │
│  Every line does what it says.                  │
│  No hidden behavior. No surprises.              │
├─────────────────────────────────────────────────┤
│  AI-Native Patterns                             │
│  ─────────────────                              │
│  Consistent, predictable code that AI           │
│  assistants can read and extend correctly.      │
├─────────────────────────────────────────────────┤
│  Minimal Abstraction                            │
│  ───────────────────                            │
│  Thin wrappers over PHP. Not frameworks         │
│  on top of frameworks.                          │
└─────────────────────────────────────────────────┘
```

---

## 🗺️ Roadmap

- [x] Route groups with shared middleware
- [x] CSRF protection middleware
- [x] Rate limiting middleware
- [x] Logging system (`Log` class + helpers)
- [x] Package auto-discovery bootstrap
- [x] `intent/auth` package (API tokens, OAuth)
- [ ] Full CMS demo application
- [ ] Package ecosystem

---

## 🤝 Contributing

Contributions welcome! Especially if you want to prove it's not "slop" 😄

1. Fork the repo
2. Create a feature branch
3. Write tests for your changes
4. Submit a PR

Check out the [ARCHITECTURE.md](./ARCHITECTURE.md) first to understand the codebase.

---

## 📄 License

MIT License. See [LICENSE](./LICENSE) for details.

---

<p align="center">
  <strong>Built with AI assistance by a non-expert. Shipped anyway.</strong>
</p>

<p align="center">
  <em>Roast me again on r/PHP if you want. I'll be here shipping v0.8.1.</em>
</p>
