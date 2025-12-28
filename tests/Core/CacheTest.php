<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Cache;
use Core\Config;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        Config::set('path.cache', sys_get_temp_dir() . '/intent_test_cache');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
    }

    public function testPutAndGet(): void
    {
        Cache::put('name', 'John');
        $this->assertEquals('John', Cache::get('name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Cache::get('nonexistent', 'default'));
        $this->assertNull(Cache::get('nonexistent'));
    }

    public function testHas(): void
    {
        $this->assertFalse(Cache::has('key'));
        Cache::put('key', 'value');
        $this->assertTrue(Cache::has('key'));
    }

    public function testForget(): void
    {
        Cache::put('key', 'value');
        $this->assertTrue(Cache::has('key'));

        Cache::forget('key');
        $this->assertFalse(Cache::has('key'));
    }

    public function testFlush(): void
    {
        Cache::put('a', 1);
        Cache::put('b', 2);
        
        Cache::flush();
        
        $this->assertFalse(Cache::has('a'));
        $this->assertFalse(Cache::has('b'));
    }

    public function testRemember(): void
    {
        $count = 0;
        $callback = function () use (&$count) {
            $count++;
            return 'computed';
        };

        // First call - computes
        $result = Cache::remember('key', 3600, $callback);
        $this->assertEquals('computed', $result);
        $this->assertEquals(1, $count);

        // Second call - from cache
        $result = Cache::remember('key', 3600, $callback);
        $this->assertEquals('computed', $result);
        $this->assertEquals(1, $count); // Not incremented
    }

    public function testIncrement(): void
    {
        $this->assertEquals(1, Cache::increment('counter'));
        $this->assertEquals(2, Cache::increment('counter'));
        $this->assertEquals(5, Cache::increment('counter', 3));
    }

    public function testDecrement(): void
    {
        Cache::put('counter', 10);
        $this->assertEquals(9, Cache::decrement('counter'));
        $this->assertEquals(6, Cache::decrement('counter', 3));
    }

    public function testExpiration(): void
    {
        // Store with 1 second TTL
        Cache::put('expires', 'value', 1);
        $this->assertEquals('value', Cache::get('expires'));

        // Wait for expiration
        sleep(2);
        $this->assertNull(Cache::get('expires'));
    }

    // ============ Tests to catch escaped mutants ============

    /**
     * Test TTL=0 means "forever" (no expiration)
     * Kills mutant: expires => $ttl > 0 ? ... : -1
     */
    public function testZeroTtlMeansForever(): void
    {
        Cache::put('forever', 'value', 0);
        
        // Should still exist (not expired)
        $this->assertTrue(Cache::has('forever'));
        $this->assertEquals('value', Cache::get('forever'));
    }

    /**
     * Test that default TTL is 0 (forever)
     * Kills mutant: $ttl = 0 → $ttl = 1 or $ttl = -1
     */
    public function testDefaultTtlIsForever(): void
    {
        Cache::put('default_ttl', 'value');
        
        // Sleep 2 seconds - should still exist if TTL=0
        sleep(2);
        $this->assertTrue(Cache::has('default_ttl'));
        $this->assertEquals('value', Cache::get('default_ttl'));
    }

    /**
     * Test TTL boundary: TTL=0 should NOT add time() to expires
     * Kills mutant: $ttl > 0 → $ttl >= 0
     */
    public function testTtlZeroBoundary(): void
    {
        // If TTL=0 incorrectly used >= 0, it would set expires = time() + 0 = now
        // and immediately expire. This test ensures TTL=0 means "never expire"
        Cache::put('boundary', 'test', 0);
        
        sleep(1);
        $this->assertEquals('test', Cache::get('boundary'));
    }

    /**
     * Test cache directory is created
     * Kills mutant: mkdir() removal
     */
    public function testCacheDirectoryCreated(): void
    {
        $tempDir = sys_get_temp_dir() . '/intent_test_' . uniqid();
        Config::set('path.cache', $tempDir);
        
        // Force path creation by putting a value
        Cache::flush(); // Reset internal path
        Cache::put('creates_dir', 'value');
        
        $this->assertTrue(Cache::has('creates_dir'));
        $this->assertEquals('value', Cache::get('creates_dir'));
        
        // Clean up
        Cache::flush();
        @rmdir($tempDir);
    }

    /**
     * Test multiple data types
     */
    public function testStoresArrays(): void
    {
        $data = ['foo' => 'bar', 'baz' => [1, 2, 3]];
        Cache::put('array', $data);
        $this->assertEquals($data, Cache::get('array'));
    }

    public function testStoresIntegers(): void
    {
        Cache::put('int', 42);
        $this->assertSame(42, Cache::get('int'));
    }

    public function testStoresBooleans(): void
    {
        Cache::put('bool_true', true);
        Cache::put('bool_false', false);
        
        $this->assertTrue(Cache::get('bool_true'));
        $this->assertFalse(Cache::get('bool_false'));
    }

    public function testStoresNull(): void
    {
        Cache::put('null_value', null);
        $this->assertNull(Cache::get('null_value'));
        $this->assertTrue(Cache::has('null_value'));
    }

    /**
     * Test forever() method
     * Kills mutant: forever($key, $value, -1)
     * Kills mutant: protected static function forever()
     */
    public function testForeverMethod(): void
    {
        Cache::forever('permanent', 'forever_value');
        
        sleep(2);
        $this->assertTrue(Cache::has('permanent'));
        $this->assertEquals('forever_value', Cache::get('permanent'));
    }

    /**
     * Test that expired items return DEFAULT value
     * Kills mutant: return $default removal
     */
    public function testExpiredReturnsDefault(): void
    {
        Cache::put('temp', 'value', 1);
        sleep(2);
        
        // Should return default when expired
        $result = Cache::get('temp', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    /**
     * Test that expired items are forgotten (removed from cache)
     * Kills mutant: self::forget($key) removal
     */
    public function testExpiredItemsAreForgotten(): void
    {
        Cache::put('expire_forget', 'value', 1);
        sleep(2);
        
        // First get should trigger forget
        Cache::get('expire_forget');
        
        // has() should return false after forget
        $this->assertFalse(Cache::has('expire_forget'));
    }

    /**
     * Test expiration check on get
     * Kills mutant: if expires > 0 && expires < time()
     */
    public function testExpirationCheckOnGet(): void
    {
        Cache::put('check_expire', 'test', 1);
        
        // Before expiration - should exist
        $this->assertTrue(Cache::has('check_expire'));
        $this->assertEquals('test', Cache::get('check_expire'));
        
        sleep(2);
        
        // After expiration - should be gone
        $this->assertNull(Cache::get('check_expire'));
        $this->assertFalse(Cache::has('check_expire'));
    }
}
