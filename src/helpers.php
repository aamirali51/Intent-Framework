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

if (!function_exists('e')) {
    /**
     * Escape HTML entities for safe output.
     * 
     * Use this in PHP views to prevent XSS attacks.
     * Twig auto-escapes, but PHP views need manual escaping.
     * 
     * Usage in PHP views:
     *   <h1><?= e($title) ?></h1>
     *   <p><?= e($userInput) ?></p>
     */
    function e(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
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
     * 
     * Auto-detects Twig if installed, falls back to plain PHP.
     * 
     * Usage:
     *   view('welcome', ['name' => 'John'])
     * 
     * With Twig installed:
     *   - Looks for resources/views/welcome.twig
     *   - Falls back to resources/views/welcome.php
     * 
     * Without Twig:
     *   - Uses resources/views/welcome.php
     * 
     * @param array<string, mixed> $data
     */
    function view(string $name, $data = []): string
    {
        // Check if Twig is installed
        if (class_exists('Twig\Environment')) {
            static $twig = null;
            
            if ($twig === null) {
                $loader = new \Twig\Loader\FilesystemLoader(BASE_PATH . '/resources/views');
                $twig = new \Twig\Environment($loader, [
                    'cache' => BASE_PATH . '/storage/cache/twig',
                    'auto_reload' => config('app.debug', false),
                    'autoescape' => 'html',
                    'strict_variables' => config('app.debug', false),
                ]);
                
                // Add Intent globals
                $twig->addGlobal('auth', auth());
                $twig->addGlobal('user', user());
                $twig->addGlobal('csrf_token', csrf_token());
                
                // Add Intent functions
                $twig->addFunction(new \Twig\TwigFunction('config', fn($key, $default = null) => config($key, $default)));
                $twig->addFunction(new \Twig\TwigFunction('session', fn($key, $default = null) => session($key, $default)));
                $twig->addFunction(new \Twig\TwigFunction('flash', fn($key) => flash($key)));
                $twig->addFunction(new \Twig\TwigFunction('csrf_field', fn() => csrf_field(), ['is_safe' => ['html']]));
                $twig->addFunction(new \Twig\TwigFunction('old', fn($key, $default = '') => session("_old.{$key}") ?? $default));
            }
            
            // Try .twig extension first
            $twigTemplate = $name . '.twig';
            if ($twig->getLoader()->exists($twigTemplate)) {
                return $twig->render($twigTemplate, $data);
            }
        }
        
        // Fallback to plain PHP
        $path = BASE_PATH . '/resources/views/' . $name . '.php';
        
        if (!file_exists($path)) {
            throw new RuntimeException("View not found: {$name}");
        }

        extract($data);
        ob_start();
        require $path;
        return ob_get_clean() ?: '';
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
     * 
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|int>> $rules
     * @param array<string, string> $messages
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
                /** @return array<string, mixed> */
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
            
            /** @return array<string, mixed>|null */
            public function user(): ?array { 
                return \Core\Auth::user();
            }
            
            public function id(): ?int { return \Core\Auth::id(); }
            public function logout(): void { \Core\Auth::logout(); }
            
            /** @param array<string, mixed> $credentials */
            public function attempt(array $credentials): bool {
                /** @var array{email?: string, password: string} $typedCredentials */
                $typedCredentials = $credentials;
                return \Core\Auth::attempt($typedCredentials);
            }
            
            /** @param array<string, mixed> $user */
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
     * 
     * @return array<string, mixed>|null
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
     * 
     * @return array<int, mixed>
     */
    function event(string $name, mixed ...$data): array
    {
        return \Core\Event::dispatch($name, ...$data) ?: [];
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
                public function has(string $key): bool { return \Core\Cache::exists($key); }
            };
        }
        
        if ($value !== null) {
            \Core\Cache::put($key, $value, $ttl);
            return $value;
        }
        
        return \Core\Cache::pull($key);
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

if (!function_exists('log_message')) {
    /**
     * Log a message to the log file.
     * 
     * WHY: Quick logging without using Log class directly.
     * 
     * Usage:
     *   log_message('info', 'User logged in', ['user_id' => 123]);
     *   log_message('error', 'Payment failed');
     * 
     * @param array<string, mixed> $context
     */
    function log_message(string $level, string $message, $context = []): void
    {
        \Core\Log::log($level, $message, $context);
    }
}

if (!function_exists('logger')) {
    /**
     * Get the logger or log a message.
     * 
     * WHY: Flexible logging access.
     * 
     * Usage:
     *   logger()->info('Message');         // Get logger
     *   logger('info', 'User login');      // Quick log
     * 
     * @param array<string, mixed> $context
     */
    function logger(?string $level = null, ?string $message = null, $context = []): object
    {
        if ($level !== null && $message !== null) {
            \Core\Log::log($level, $message, $context);
        }

        return new class {
            /** @param array<string, mixed> $ctx */
            public function debug(string $msg, array $ctx = []): void { \Core\Log::debug($msg, $ctx); }
            /** @param array<string, mixed> $ctx */
            public function info(string $msg, array $ctx = []): void { \Core\Log::info($msg, $ctx); }
            /** @param array<string, mixed> $ctx */
            public function warning(string $msg, array $ctx = []): void { \Core\Log::warning($msg, $ctx); }
            /** @param array<string, mixed> $ctx */
            public function error(string $msg, array $ctx = []): void { \Core\Log::error($msg, $ctx); }
            /** @param array<string, mixed> $ctx */
            public function critical(string $msg, array $ctx = []): void { \Core\Log::critical($msg, $ctx); }
        };
    }
}

if (!function_exists('upload')) {
    /**
     * Get an upload instance.
     */
    function upload(string $key): \Core\Upload
    {
        return \Core\Upload::file($key);
    }
}

if (!function_exists('slug')) {
    /**
     * Generate a URL-safe slug from a string.
     */
    function slug(string $text, string $separator = '-'): string
    {
        // Convert to lowercase
        $text = mb_strtolower($text, 'UTF-8');
        
        // Replace non-alphanumeric characters with separator
        $text = preg_replace('/[^a-z0-9]+/', $separator, $text);
        
        // Remove leading/trailing separators
        $text = trim($text ?? '', $separator);
        
        // Remove duplicate separators
        $text = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $text);
        
        return (string) $text;
    }
}

if (!function_exists('api_token')) {
    /**
     * Generate an API token for a user.
     * 
     * Usage:
     *   $token = api_token($userId);
     *   $token = api_token($userId, 'mobile-app', 3600);
     */
    function api_token(int $userId, string $name = 'default', ?int $ttl = null): string
    {
        return \Core\ApiToken::create($userId, $name, $ttl);
    }
}

if (!function_exists('oauth_redirect')) {
    /**
     * Get OAuth redirect URL for a provider.
     * 
     * Usage:
     *   return redirect(oauth_redirect('google'));
     *   return redirect(oauth_redirect('github', ['scope' => 'user:email']));
     * 
     * @param array<string, mixed> $options
     */
    function oauth_redirect(string $provider, $options = []): string
    {
        /** @var array<string, string> $typedOptions */
        $typedOptions = [];
        foreach ($options as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $typedOptions[$key] = $value;
            }
        }
        return \Core\OAuth::redirect($provider, $typedOptions);
    }
}
