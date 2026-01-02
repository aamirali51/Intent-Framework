<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\ApiToken;
use Core\DB;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ApiToken class.
 */
class ApiTokenTest extends TestCase
{
    private static bool $tableCreated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test database table
        if (!self::$tableCreated) {
            try {
                $pdo = DB::connection();
                
                $pdo->exec("DROP TABLE IF EXISTS api_tokens");
                $pdo->exec("DROP TABLE IF EXISTS users");
                
                $pdo->exec("
                    CREATE TABLE users (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        email TEXT NOT NULL,
                        password TEXT NOT NULL,
                        name TEXT
                    )
                ");
                
                $pdo->exec("
                    CREATE TABLE api_tokens (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        name TEXT DEFAULT 'default',
                        token TEXT NOT NULL UNIQUE,
                        last_used_at TEXT NULL,
                        expires_at TEXT NULL,
                        created_at TEXT DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                // Create test user
                DB::table('users')->insert([
                    'email' => 'test@example.com',
                    'password' => password_hash('secret', PASSWORD_BCRYPT),
                    'name' => 'Test User',
                ]);
                
                self::$tableCreated = true;
            } catch (\Exception $e) {
                // Tables might already exist
            }
        }

        ApiToken::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up tokens between tests
        try {
            DB::connection()->exec("DELETE FROM api_tokens WHERE 1=1");
        } catch (\Exception $e) {
            // Table might not exist
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Token Generation Tests
    // ─────────────────────────────────────────────────────────────

    public function testCreateGeneratesToken(): void
    {
        $token = ApiToken::create(1);
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCreateGeneratesUniqueTokens(): void
    {
        $token1 = ApiToken::create(1);
        $token2 = ApiToken::create(1);
        
        $this->assertNotEquals($token1, $token2);
    }

    public function testCreateStoresTokenInDatabase(): void
    {
        $token = ApiToken::create(1, 'test-token');
        
        $records = DB::table('api_tokens')->where('user_id', 1)->get();
        
        $this->assertCount(1, $records);
        $this->assertEquals('test-token', $records[0]['name']);
    }

    public function testCreateWithTtlSetsExpiration(): void
    {
        $token = ApiToken::create(1, 'expiring', 3600);
        
        $record = DB::table('api_tokens')->where('user_id', 1)->first();
        
        $this->assertNotNull($record['expires_at']);
        
        $expiresAt = strtotime($record['expires_at']);
        $expectedExpiry = time() + 3600;
        
        // Allow 5 second tolerance
        $this->assertEqualsWithDelta($expectedExpiry, $expiresAt, 5);
    }

    // ─────────────────────────────────────────────────────────────
    // Token Validation Tests
    // ─────────────────────────────────────────────────────────────

    public function testValidateReturnsUserForValidToken(): void
    {
        $token = ApiToken::create(1);
        
        $user = ApiToken::validate($token);
        
        $this->assertIsArray($user);
        $this->assertEquals(1, $user['id']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertArrayNotHasKey('password', $user);
    }

    public function testValidateReturnsNullForInvalidToken(): void
    {
        $user = ApiToken::validate('invalid-token');
        
        $this->assertNull($user);
    }

    public function testValidateReturnsNullForEmptyToken(): void
    {
        $user = ApiToken::validate('');
        
        $this->assertNull($user);
    }

    public function testValidateReturnsNullForExpiredToken(): void
    {
        // Create token that expires in 1 second
        $token = ApiToken::create(1, 'expiring', 1);
        
        // Wait for expiration
        sleep(2);
        
        $user = ApiToken::validate($token);
        
        $this->assertNull($user);
    }

    public function testValidateUpdatesLastUsedAt(): void
    {
        $token = ApiToken::create(1);
        
        // First check - no last_used_at
        $recordBefore = DB::table('api_tokens')->first();
        $this->assertNull($recordBefore['last_used_at']);
        
        // Validate token
        ApiToken::validate($token);
        
        // Check last_used_at is set
        $recordAfter = DB::table('api_tokens')->first();
        $this->assertNotNull($recordAfter['last_used_at']);
    }

    // ─────────────────────────────────────────────────────────────
    // Token Revocation Tests
    // ─────────────────────────────────────────────────────────────

    public function testRevokeDeletesToken(): void
    {
        $token = ApiToken::create(1);
        $record = DB::table('api_tokens')->first();
        
        $result = ApiToken::revoke((int) $record['id']);
        
        $this->assertTrue($result);
        $this->assertNull(ApiToken::validate($token));
    }

    public function testRevokeTokenDeletesByPlainToken(): void
    {
        $token = ApiToken::create(1);
        
        $result = ApiToken::revokeToken($token);
        
        $this->assertTrue($result);
        $this->assertNull(ApiToken::validate($token));
    }

    public function testRevokeAllDeletesAllUserTokens(): void
    {
        $token1 = ApiToken::create(1, 'token-1');
        $token2 = ApiToken::create(1, 'token-2');
        $token3 = ApiToken::create(1, 'token-3');
        
        $deleted = ApiToken::revokeAll(1);
        
        $this->assertEquals(3, $deleted);
        $this->assertNull(ApiToken::validate($token1));
        $this->assertNull(ApiToken::validate($token2));
        $this->assertNull(ApiToken::validate($token3));
    }

    // ─────────────────────────────────────────────────────────────
    // Token Management Tests
    // ─────────────────────────────────────────────────────────────

    public function testUserTokensReturnsTokenList(): void
    {
        ApiToken::create(1, 'mobile-app');
        ApiToken::create(1, 'desktop-app');
        
        $tokens = ApiToken::userTokens(1);
        
        $this->assertCount(2, $tokens);
        
        // Check both names exist (order may vary due to same timestamp)
        $names = array_column($tokens, 'name');
        $this->assertContains('mobile-app', $names);
        $this->assertContains('desktop-app', $names);
        
        // Should not include actual token hash
        $this->assertArrayNotHasKey('token', $tokens[0]);
    }

    public function testPruneExpiredRemovesOldTokens(): void
    {
        // Create token that expires immediately
        $token = ApiToken::create(1, 'expiring', 1);
        sleep(2);
        
        $deleted = ApiToken::pruneExpired();
        
        $this->assertEquals(1, $deleted);
    }

    public function testCheckReturnsTrueForValidToken(): void
    {
        $token = ApiToken::create(1);
        
        $this->assertTrue(ApiToken::check($token));
    }

    public function testCheckReturnsFalseForInvalidToken(): void
    {
        $this->assertFalse(ApiToken::check('invalid'));
    }

    // ─────────────────────────────────────────────────────────────
    // Configuration Tests
    // ─────────────────────────────────────────────────────────────

    public function testConfigureChangesTableNames(): void
    {
        // This should not throw - just verify the method exists
        ApiToken::configure('custom_tokens', 'custom_users', 7200);
        
        // Reset for other tests
        ApiToken::reset();
        
        $this->assertTrue(true);
    }

    public function testResetRestoresDefaults(): void
    {
        ApiToken::configure('custom', 'custom', 999);
        ApiToken::reset();
        
        // Should work with default tables
        $token = ApiToken::create(1);
        $this->assertNotEmpty($token);
    }
}
