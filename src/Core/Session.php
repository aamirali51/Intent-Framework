<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple session management.
 * 
 * Wraps PHP's native session with a clean API.
 * Supports flash messages (one-time data).
 * 
 * Usage:
 *   Session::start();
 *   Session::set('user_id', 123);
 *   Session::get('user_id');
 *   Session::flash('success', 'Welcome back!');
 */
final class Session
{
    private static bool $started = false;
    private const FLASH_KEY = '_flash';
    private const FLASH_OLD_KEY = '_flash_old';

    /**
     * Start the session.
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        self::$started = true;
        self::ageFlashData();
    }

    /**
     * Ensure session is started.
     */
    private static function ensureStarted(): void
    {
        if (!self::$started) {
            self::start();
        }
    }

    /**
     * Get a session value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value.
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists.
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value.
     */
    public static function forget(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data.
     * 
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        self::ensureStarted();
        return $_SESSION;
    }

    /**
     * Clear all session data.
     */
    public static function clear(): void
    {
        self::ensureStarted();
        $_SESSION = [];
    }

    /**
     * Destroy the session completely.
     */
    public static function destroy(): void
    {
        self::ensureStarted();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name() ?: 'PHPSESSID',
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        self::$started = false;
    }

    /**
     * Regenerate session ID.
     */
    public static function regenerate(): void
    {
        self::ensureStarted();
        session_regenerate_id(true);
    }

    /**
     * Get the session ID.
     */
    public static function id(): string
    {
        self::ensureStarted();
        return session_id() ?: '';
    }

    // ─────────────────────────────────────────────────────────────
    // Flash Messages (one-time data)
    // ─────────────────────────────────────────────────────────────

    /**
     * Set a flash message (available only on next request).
     */
    public static function flash(string $key, mixed $value): void
    {
        self::ensureStarted();
        
        if (!isset($_SESSION[self::FLASH_KEY]) || !is_array($_SESSION[self::FLASH_KEY])) {
            $_SESSION[self::FLASH_KEY] = [];
        }
        
        /** @var array<string, mixed> $flashData */
        $flashData = $_SESSION[self::FLASH_KEY];
        $flashData[$key] = $value;
        $_SESSION[self::FLASH_KEY] = $flashData;
    }

    /**
     * Get a flash message.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        
        // Check old flash (from previous request)
        /** @var array<string, mixed>|null $flashOld */
        $flashOld = $_SESSION[self::FLASH_OLD_KEY] ?? null;
        if (is_array($flashOld) && isset($flashOld[$key])) {
            return $flashOld[$key];
        }
        
        return $default;
    }

    /**
     * Get all flash messages.
     * 
     * @return array<string, mixed>
     */
    public static function getFlashes(): array
    {
        self::ensureStarted();
        /** @var array<string, mixed> $flashes */
        $flashes = $_SESSION[self::FLASH_OLD_KEY] ?? [];
        return is_array($flashes) ? $flashes : [];
    }

    /**
     * Check if a flash message exists.
     */
    public static function hasFlash(string $key): bool
    {
        self::ensureStarted();
        /** @var array<string, mixed>|null $flashOld */
        $flashOld = $_SESSION[self::FLASH_OLD_KEY] ?? null;
        return is_array($flashOld) && isset($flashOld[$key]);
    }

    /**
     * Age flash data - move current flash to old, clear old.
     */
    private static function ageFlashData(): void
    {
        // Move current flash to old (for reading this request)
        $_SESSION[self::FLASH_OLD_KEY] = $_SESSION[self::FLASH_KEY] ?? [];
        
        // Clear current flash (ready for new flash data)
        $_SESSION[self::FLASH_KEY] = [];
    }

    // ─────────────────────────────────────────────────────────────
    // Convenience Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Push a value onto an array session value.
     */
    public static function push(string $key, mixed $value): void
    {
        self::ensureStarted();
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        
        if (!is_array($_SESSION[$key])) {
            throw new \RuntimeException("Session key '{$key}' is not an array");
        }
        
        $_SESSION[$key][] = $value;
    }

    /**
     * Increment a numeric session value.
     */
    public static function increment(string $key, int $amount = 1): int
    {
        self::ensureStarted();
        
        /** @var int $current */
        $current = (int) ($_SESSION[$key] ?? 0);
        $new = $current + $amount;
        $_SESSION[$key] = $new;
        
        return $new;
    }

    /**
     * Decrement a numeric session value.
     */
    public static function decrement(string $key, int $amount = 1): int
    {
        return self::increment($key, -$amount);
    }

    // ─────────────────────────────────────────────────────────────
    // Testing Support
    // ─────────────────────────────────────────────────────────────

    /**
     * Reset all static state (for testing or long-running processes).
     */
    public static function reset(): void
    {
        self::$started = false;
        $_SESSION = [];
    }

    /**
     * Fake session data for testing (without starting real session).
     * 
     * Usage in tests:
     *   Session::fake(['user_id' => 1, 'role' => 'admin']);
     * 
     * @param array<string, mixed> $data
     */
    public static function fake(array $data = []): void
    {
        self::$started = true;
        $_SESSION = $data;
    }
}
