<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\OAuth;
use Core\Session;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for OAuth class.
 * 
 * Note: Full OAuth flow requires real provider credentials.
 * These tests verify URL generation and state handling.
 */
class OAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        // Clear session data
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    // ─────────────────────────────────────────────────────────────
    // Provider Tests
    // ─────────────────────────────────────────────────────────────

    public function testProvidersReturnsAllSupportedProviders(): void
    {
        $providers = OAuth::providers();
        
        $this->assertContains('google', $providers);
        $this->assertContains('github', $providers);
        $this->assertContains('facebook', $providers);
    }

    public function testHasProviderReturnsFalseForUnconfiguredProvider(): void
    {
        // Without config, should return false
        $this->assertFalse(OAuth::hasProvider('google'));
    }

    // ─────────────────────────────────────────────────────────────
    // Redirect URL Tests
    // ─────────────────────────────────────────────────────────────

    public function testRedirectThrowsForUnsupportedProvider(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported OAuth provider');
        
        OAuth::redirect('unsupported');
    }

    public function testRedirectThrowsForUnconfiguredProvider(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not configured');
        
        OAuth::redirect('google');
    }

    // ─────────────────────────────────────────────────────────────
    // Callback Tests
    // ─────────────────────────────────────────────────────────────

    public function testCallbackThrowsForInvalidState(): void
    {
        Session::start();
        Session::set('oauth_state', 'expected-state');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid OAuth state');
        
        OAuth::callback('google', 'code', 'wrong-state');
    }
}
