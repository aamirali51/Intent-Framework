<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple authentication.
 * 
 * Session-based auth with password hashing.
 * No complex guards or providers - just login, logout, check.
 * 
 * Usage:
 *   Auth::attempt(['email' => 'john@test.com', 'password' => 'secret']);
 *   Auth::check();
 *   Auth::user();
 *   Auth::logout();
 */
final class Auth
{
    private const SESSION_KEY = '_auth_user_id';
    private const SESSION_USER = '_auth_user';

    private static ?array $user = null;
    private static string $table = 'users';
    private static string $usernameField = 'email';
    private static string $passwordField = 'password';

    /**
     * Configure the auth table and fields.
     */
    public static function configure(
        string $table = 'users',
        string $usernameField = 'email',
        string $passwordField = 'password'
    ): void {
        self::$table = $table;
        self::$usernameField = $usernameField;
        self::$passwordField = $passwordField;
    }

    /**
     * Attempt to log in with credentials.
     * 
     * @param array{email?: string, password: string} $credentials
     * @return bool True if login successful
     */
    public static function attempt(array $credentials): bool
    {
        Session::start();

        $username = $credentials[self::$usernameField] ?? '';
        $password = $credentials['password'] ?? '';

        if ($username === '' || $password === '') {
            return false;
        }

        // Find user by username/email
        $user = DB::table(self::$table)
            ->where(self::$usernameField, $username)
            ->first();

        if ($user === null) {
            return false;
        }

        // Verify password
        if (!password_verify($password, $user[self::$passwordField])) {
            return false;
        }

        // Login successful - store in session
        self::login($user);

        return true;
    }

    /**
     * Log in a user directly (without password check).
     */
    public static function login(array $user): void
    {
        Session::start();

        // Remove password from stored user
        unset($user[self::$passwordField]);

        Session::set(self::SESSION_KEY, $user['id'] ?? null);
        Session::set(self::SESSION_USER, $user);
        Session::regenerate();

        self::$user = $user;
    }

    /**
     * Log out the current user.
     */
    public static function logout(): void
    {
        Session::start();
        Session::forget(self::SESSION_KEY);
        Session::forget(self::SESSION_USER);
        Session::regenerate();

        self::$user = null;
    }

    /**
     * Check if a user is logged in.
     */
    public static function check(): bool
    {
        Session::start();
        return Session::has(self::SESSION_KEY);
    }

    /**
     * Check if user is a guest (not logged in).
     */
    public static function guest(): bool
    {
        return !self::check();
    }

    /**
     * Get the current authenticated user.
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        if (self::$user !== null) {
            return self::$user;
        }

        self::$user = Session::get(self::SESSION_USER);
        return self::$user;
    }

    /**
     * Get the current user's ID.
     */
    public static function id(): ?int
    {
        if (!self::check()) {
            return null;
        }

        $id = Session::get(self::SESSION_KEY);
        return $id !== null ? (int) $id : null;
    }

    /**
     * Hash a password for storage.
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify a password against a hash.
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing.
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT);
    }

    // ─────────────────────────────────────────────────────────────
    // Convenience Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a new user with hashed password.
     */
    public static function createUser(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = self::hash($data['password']);
        }

        if (isset($data['password_confirmation'])) {
            unset($data['password_confirmation']);
        }

        return DB::table(self::$table)->insert($data);
    }

    /**
     * Find user by ID.
     */
    public static function findById(int $id): ?array
    {
        $user = DB::table(self::$table)->find($id);
        
        if ($user !== null) {
            unset($user[self::$passwordField]);
        }
        
        return $user;
    }

    /**
     * Find user by email/username.
     */
    public static function findByUsername(string $username): ?array
    {
        $user = DB::table(self::$table)
            ->where(self::$usernameField, $username)
            ->first();
        
        if ($user !== null) {
            unset($user[self::$passwordField]);
        }
        
        return $user;
    }
}
