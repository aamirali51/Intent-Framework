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
}
