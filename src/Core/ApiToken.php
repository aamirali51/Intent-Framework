<?php

declare(strict_types=1);

namespace Core;

/**
 * API Token Authentication.
 * 
 * Stateless token-based auth for APIs.
 * Tokens are stored hashed (like passwords) for security.
 * 
 * Usage:
 *   $token = ApiToken::create($userId);              // Generate token
 *   $token = ApiToken::create($userId, 'mobile', 3600); // With name + TTL
 *   $user = ApiToken::validate($plainToken);         // Returns user or null
 *   ApiToken::revoke($tokenId);                      // Revoke single token
 *   ApiToken::revokeAll($userId);                    // Revoke all user tokens
 */
final class ApiToken
{
    private static string $table = 'api_tokens';
    private static string $userTable = 'users';
    private static ?int $defaultTtl = null; // null = never expires

    /**
     * Configure the token table and defaults.
     */
    public static function configure(
        string $table = 'api_tokens',
        string $userTable = 'users',
        ?int $defaultTtl = null
    ): void {
        self::$table = $table;
        self::$userTable = $userTable;
        self::$defaultTtl = $defaultTtl;
    }

    /**
     * Create a new API token for a user.
     * 
     * @param int $userId User ID to create token for
     * @param string $name Token name (e.g., 'mobile-app', 'api-key')
     * @param int|null $ttl Time to live in seconds (null = use default)
     * @return string Plain token (show to user ONCE, never stored)
     */
    public static function create(int $userId, string $name = 'default', ?int $ttl = null): string
    {
        // Generate a secure random token
        $plainToken = self::generateToken();
        
        // Hash for storage (like passwords)
        $hashedToken = self::hashToken($plainToken);
        
        // Calculate expiration
        $ttl = $ttl ?? self::$defaultTtl ?? self::getConfigTtl();
        $expiresAt = $ttl !== null ? date('Y-m-d H:i:s', time() + $ttl) : null;
        
        // Store in database
        DB::table(self::$table)->insert([
            'user_id' => $userId,
            'name' => $name,
            'token' => $hashedToken,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Return plain token (user sees this once)
        return $plainToken;
    }

    /**
     * Validate a token and return the associated user.
     * 
     * @param string $plainToken The bearer token from request
     * @return array|null User array if valid, null if invalid/expired
     */
    /**
     * Validate a token and return the associated user.
     * 
     * @param string $plainToken The bearer token from request
     * @return array<string, mixed>|null User array if valid, null if invalid/expired
     */
    public static function validate(string $plainToken): ?array
    {
        if ($plainToken === '') {
            return null;
        }

        $hashedToken = self::hashToken($plainToken);
        
        // Find token record
        $tokenRecord = DB::table(self::$table)
            ->where('token', $hashedToken)
            ->first();
        
        if ($tokenRecord === null) {
            return null;
        }
        
        // Check expiration
        if ($tokenRecord['expires_at'] !== null) {
            /** @var string $expiresAtString */
            $expiresAtString = $tokenRecord['expires_at'];
            $expiresAt = strtotime($expiresAtString);
            if ($expiresAt !== false && $expiresAt < time()) {
                // Token expired - clean it up
                /** @var int $tokenId */
                $tokenId = $tokenRecord['id'];
                self::revoke($tokenId);
                return null;
            }
        }
        
        // Update last_used_at
        DB::table(self::$table)
            ->where('id', $tokenRecord['id'])
            ->update(['last_used_at' => date('Y-m-d H:i:s')]);
        
        // Fetch and return user
        /** @var int $userId */
        $userId = $tokenRecord['user_id'];
        $user = DB::table(self::$userTable)->find($userId);
        
        if ($user !== null) {
            // Remove password field for security
            unset($user['password']);
        }
        
        return $user;
    }

    /**
     * Extract bearer token from request.
     * 
     * Checks Authorization header and query parameter.
     * 
     * @param Request $request The HTTP request
     * @return string|null Token if found, null otherwise
     */
    public static function fromRequest(Request $request): ?string
    {
        // Check Authorization header first
        /** @var string $header */
        $header = $request->header('Authorization') ?? $request->header('authorization') ?? '';
        
        if (is_string($header) && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        
        // Fallback to query parameter (for testing/debugging)
        $queryToken = $request->get('api_token');
        if (is_string($queryToken) && $queryToken !== '') {
            return $queryToken;
        }
        
        return null;
    }

    /**
     * Revoke a specific token by ID.
     */
    public static function revoke(int $tokenId): bool
    {
        return DB::table(self::$table)
            ->where('id', $tokenId)
            ->delete() > 0;
    }

    /**
     * Revoke a token by its plain value.
     */
    public static function revokeToken(string $plainToken): bool
    {
        $hashedToken = self::hashToken($plainToken);
        
        return DB::table(self::$table)
            ->where('token', $hashedToken)
            ->delete() > 0;
    }

    /**
     * Revoke all tokens for a user.
     */
    public static function revokeAll(int $userId): int
    {
        return DB::table(self::$table)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Get all tokens for a user (for management UI).
     * 
     * @return array<int, array<string, mixed>> List of token records (without actual token values)
     */
    public static function userTokens(int $userId): array
    {
        return DB::table(self::$table)
            ->select(['id', 'name', 'last_used_at', 'expires_at', 'created_at'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Clean up expired tokens.
     * 
     * Call this periodically (e.g., via cron job).
     * 
     * @return int Number of tokens deleted
     */
    public static function pruneExpired(): int
    {
        return DB::table(self::$table)
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    /**
     * Check if a token exists and is valid (without fetching user).
     */
    public static function check(string $plainToken): bool
    {
        return self::validate($plainToken) !== null;
    }

    // ─────────────────────────────────────────────────────────────
    // Internal Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Generate a cryptographically secure random token.
     * 
     * Format: 64 hex characters (256 bits of entropy)
     */
    private static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Hash a token for storage.
     * 
     * Uses SHA-256 for fast lookups (unlike bcrypt for passwords).
     * Security: Even if DB is leaked, attacker can't use tokens
     * without knowing the original plain value.
     */
    private static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Get default TTL from config.
     */
    private static function getConfigTtl(): ?int
    {
        $config = Config::get('auth.api_tokens.expires');
        return is_int($config) ? $config : null;
    }

    // ─────────────────────────────────────────────────────────────
    // Testing Support
    // ─────────────────────────────────────────────────────────────

    /**
     * Reset all static state (for testing).
     */
    public static function reset(): void
    {
        self::$table = 'api_tokens';
        self::$userTable = 'users';
        self::$defaultTtl = null;
    }
}
