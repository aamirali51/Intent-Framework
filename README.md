<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-8892BF?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Composer-PSR--4-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer">
  <img src="https://img.shields.io/badge/Tests-69%20Passing-brightgreen?style=for-the-badge" alt="Tests">
  <img src="https://img.shields.io/badge/Version-0.3.0-blue?style=for-the-badge" alt="Version">
</p>

<h1 align="center">Intent Framework</h1>

<p align="center">
  <strong>A zero-boilerplate, AI-native, explicitly designed PHP micro-framework</strong>
</p>

<p align="center">
  ~3,000 lines of core code. No magic. No facades. No containers.<br>
  Just PHP that reads like English.
</p>

<p align="center">
  <em>Yes, most of this was written by AI. Yes, I'm not a PHP expert. No, I don't care.</em>
</p>

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

// Cache with remember pattern
$users = Cache::remember('users', 3600, fn() => 
    DB::table('users')->get()
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
| "No tests" | **69 tests, 124 assertions** via PHPUnit |
| "Incomplete" | v0.3.0 has middleware, auth, sessions, events, cache, CLI |
| "No Composer" | Full `composer.json`, PSR-4 autoloading, proper `vendor/` |
| "Bad structure" | `public/` separation, `core/` for framework, `app/` for user code |
| "Just use Laravel" | Sure — if you want facades and service containers. This is the opposite. |

> The whole point of Intent is that it's **readable, predictable, and AI-friendly**.  
> Whether a human or an AI writes the code, it should be obvious what it does.

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
| **Query Builder** | Returns arrays, not objects. `DB::table('users')->get()` |
| **Dev Schema** | Auto-creates tables in dev mode (disabled in prod) |
| **Secure File Routes** | Outside `public/`, in `app/Api/` |
| **CLI Tool** | `php intent serve`, `php intent make:handler` |

---

## 📦 Installation

**Option 1: Clone**
```bash
git clone https://github.com/yourusername/intent.git my-app
cd my-app
composer install
```

**Option 2: Fresh start**
```bash
composer create-project intent/framework my-app
cd my-app
```

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

## 📚 Documentation

Full technical documentation is in [`ARCHITECTURE.md`](./ARCHITECTURE.md):

- Directory structure
- All 15 core classes explained
- Every helper function
- Security features
- Configuration options

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Or directly
vendor/bin/phpunit
```

**Current coverage:** 69 tests, 124 assertions

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

- [ ] Route groups with shared middleware
- [x] CSRF protection middleware
- [ ] Rate limiting middleware
- [ ] `intent/auth` package (OAuth, API tokens)
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
  <em>Roast me again on r/PHP if you want. I'll be here shipping v0.4.0.</em>
</p>
