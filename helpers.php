<?php

declare(strict_types=1);

/**
 * Global helper functions.
 * 
 * Keep these minimal. Only add truly universal helpers.
 */

if (!function_exists('config')) {
    /**
     * Get a configuration value.
     */
    function config(string $key, mixed $default = null): mixed
    {
        return \Core\Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template.
     */
    function view(string $name, array $data = []): string
    {
        $path = BASE_PATH . '/views/' . $name . '.php';
        
        if (!file_exists($path)) {
            throw new RuntimeException("View not found: {$name}");
        }

        extract($data);
        ob_start();
        require $path;
        return ob_get_clean();
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response.
     */
    function redirect(string $url, int $status = 302): \Core\Response
    {
        return (new \Core\Response())->redirect($url, $status);
    }
}

if (!function_exists('json')) {
    /**
     * Create a JSON response.
     * 
     * WHY: Shorthand for common JSON response pattern.
     * Calls: new Response()->json()
     */
    function json(mixed $data, int $status = 200): \Core\Response
    {
        return (new \Core\Response())->json($data, $status);
    }
}

if (!function_exists('request')) {
    /**
     * Create a new Request instance.
     * 
     * WHY: Access request data without injecting Request into every function.
     * Calls: new Request() - always creates fresh instance from superglobals.
     */
    function request(): \Core\Request
    {
        return new \Core\Request();
    }
}

if (!function_exists('response')) {
    /**
     * Create a new Response instance.
     * 
     * WHY: Shorthand for response building without 'use' statement.
     * Calls: new Response() - fresh instance, no hidden state.
     */
    function response(): \Core\Response
    {
        return new \Core\Response();
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die. Debug helper.
     * 
     * WHY: Quick debugging during development.
     * Dumps all passed variables and terminates execution.
     */
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#1a1a1a;color:#fafafa;padding:1rem;margin:0;font-family:monospace;">';
        foreach ($vars as $i => $var) {
            if ($i > 0) echo "\n" . str_repeat('─', 50) . "\n";
            var_dump($var);
        }
        echo '</pre>';
        exit(1);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data against rules.
     * 
     * WHY: Quick validation without instantiating Validator.
     * Calls: Validator::make()
     */
    function validate(array $data, array $rules, array $messages = []): \Core\Validator
    {
        return \Core\Validator::make($data, $rules, $messages);
    }
}

if (!function_exists('session')) {
    /**
     * Get or set session values.
     * 
     * WHY: English-like session access.
     * 
     * Usage:
     *   session('user_id')           // Get value
     *   session('user_id', 123)      // Set value
     *   session()->all()             // Get Session class
     */
    function session(?string $key = null, mixed $value = null): mixed
    {
        \Core\Session::start();
        
        if ($key === null) {
            return new class {
                public function all(): array { return \Core\Session::all(); }
                public function clear(): void { \Core\Session::clear(); }
                public function destroy(): void { \Core\Session::destroy(); }
                public function regenerate(): void { \Core\Session::regenerate(); }
            };
        }
        
        if ($value !== null) {
            \Core\Session::set($key, $value);
            return $value;
        }
        
        return \Core\Session::get($key);
    }
}

if (!function_exists('flash')) {
    /**
     * Get or set flash messages.
     * 
     * WHY: One-time messages (success, error) for next request.
     * 
     * Usage:
     *   flash('success', 'Saved!')   // Set flash
     *   flash('success')              // Get flash
     */
    function flash(string $key, mixed $value = null): mixed
    {
        \Core\Session::start();
        
        if ($value !== null) {
            \Core\Session::flash($key, $value);
            return $value;
        }
        
        return \Core\Session::getFlash($key);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the Auth class or check authentication.
     * 
     * WHY: English-like auth access.
     * 
     * Usage:
     *   auth()->check()   // Is logged in?
     *   auth()->user()    // Get current user
     *   auth()->id()      // Get current user ID
     *   auth()->logout()  // Log out
     */
    function auth(): object
    {
        return new class {
            public function check(): bool { return \Core\Auth::check(); }
            public function guest(): bool { return \Core\Auth::guest(); }
            public function user(): ?array { return \Core\Auth::user(); }
            public function id(): ?int { return \Core\Auth::id(); }
            public function logout(): void { \Core\Auth::logout(); }
            
            public function attempt(array $credentials): bool {
                return \Core\Auth::attempt($credentials);
            }
            
            public function login(array $user): void {
                \Core\Auth::login($user);
            }
        };
    }
}

if (!function_exists('user')) {
    /**
     * Get the current authenticated user.
     * 
     * WHY: Quick access to logged-in user.
     * 
     * Usage:
     *   user()              // Get user array
     *   user()['name']      // Get user's name
     */
    function user(): ?array
    {
        return \Core\Auth::user();
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event.
     * 
     * WHY: Trigger actions without coupling code.
     * 
     * Usage:
     *   event('user.created', $user);
     */
    function event(string $name, mixed ...$data): array
    {
        return \Core\Event::dispatch($name, ...$data);
    }
}

if (!function_exists('cache')) {
    /**
     * Get or set cache values.
     * 
     * WHY: Simple caching.
     * 
     * Usage:
     *   cache('key');                    // Get
     *   cache('key', $value, 3600);      // Set for 1 hour
     *   cache()->flush();                // Clear all
     */
    function cache(?string $key = null, mixed $value = null, int $ttl = 0): mixed
    {
        if ($key === null) {
            return new class {
                public function flush(): void { \Core\Cache::flush(); }
                public function forget(string $key): void { \Core\Cache::forget($key); }
                public function has(string $key): bool { return \Core\Cache::has($key); }
            };
        }
        
        if ($value !== null) {
            \Core\Cache::put($key, $value, $ttl);
            return $value;
        }
        
        return \Core\Cache::get($key);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token.
     * 
     * WHY: Quick access to CSRF token for forms and AJAX.
     * 
     * Usage:
     *   <input type="hidden" name="_token" value="<?= csrf_token() ?>">
     */
    function csrf_token(): string
    {
        return \App\Middleware\CsrfMiddleware::getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a hidden CSRF token input field.
     * 
     * WHY: One-liner for form protection.
     * 
     * Usage:
     *   <form method="POST">
     *       <?= csrf_field() ?>
     *       ...
     *   </form>
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
