<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Cache;
use Core\Config;
use Core\InvalidCacheKeyException;
use DateInterval;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::reset();
        Config::set('path.cache', sys_get_temp_dir() . '/intent_test_cache');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Cache::reset();
    }

    // ============ PSR-16 Compliance Tests ============

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, Cache::instance());
    }

    public function testPsr16SetAndGet(): void
    {
        $cache = Cache::instance();
        $this->assertTrue($cache->set('psr16_key', 'psr16_value'));
        $this->assertEquals('psr16_value', $cache->get('psr16_key'));
    }

    public function testPsr16Delete(): void
    {
        $cache = Cache::instance();
        $cache->set('to_delete', 'value');
        $this->assertTrue($cache->delete('to_delete'));
        $this->assertNull($cache->get('to_delete'));
    }

    public function testPsr16DeleteNonExistent(): void
    {
        $cache = Cache::instance();
        // PSR-16: deleting non-existent key returns true
        $this->assertTrue($cache->delete('never_existed'));
    }

    public function testPsr16Clear(): void
    {
        $cache = Cache::instance();
        $cache->set('a', 1);
        $cache->set('b', 2);
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->has('a'));
        $this->assertFalse($cache->has('b'));
    }

    public function testPsr16Has(): void
    {
        $cache = Cache::instance();
        $this->assertFalse($cache->has('missing'));
        $cache->set('exists', 'value');
        $this->assertTrue($cache->has('exists'));
    }

    public function testPsr16GetMultiple(): void
    {
        $cache = Cache::instance();
        $cache->set('multi_a', 'value_a');
        $cache->set('multi_b', 'value_b');
        
        $result = $cache->getMultiple(['multi_a', 'multi_b', 'multi_c'], 'default');
        
        $this->assertEquals([
            'multi_a' => 'value_a',
            'multi_b' => 'value_b',
            'multi_c' => 'default',
        ], $result);
    }

    public function testPsr16SetMultiple(): void
    {
        $cache = Cache::instance();
        $this->assertTrue($cache->setMultiple([
            'batch_a' => 'value_a',
            'batch_b' => 'value_b',
        ]));
        
        $this->assertEquals('value_a', $cache->get('batch_a'));
        $this->assertEquals('value_b', $cache->get('batch_b'));
    }

    public function testPsr16DeleteMultiple(): void
    {
        $cache = Cache::instance();
        $cache->set('del_a', 1);
        $cache->set('del_b', 2);
        
        $this->assertTrue($cache->deleteMultiple(['del_a', 'del_b']));
        $this->assertFalse($cache->has('del_a'));
        $this->assertFalse($cache->has('del_b'));
    }

    public function testPsr16DateIntervalTtl(): void
    {
        $cache = Cache::instance();
        $cache->set('interval_key', 'interval_value', new DateInterval('PT2S'));
        
        $this->assertEquals('interval_value', $cache->get('interval_key'));
        
        sleep(3);
        $this->assertNull($cache->get('interval_key'));
    }

    public function testPsr16ZeroTtlDeletesItem(): void
    {
        $cache = Cache::instance();
        $cache->set('will_be_deleted', 'value');
        
        // PSR-16: TTL of 0 or negative should delete the item
        $cache->set('will_be_deleted', 'new_value', 0);
        $this->assertFalse($cache->has('will_be_deleted'));
    }

    public function testPsr16NegativeTtlDeletesItem(): void
    {
        $cache = Cache::instance();
        $cache->set('negative_ttl', 'value');
        
        // PSR-16: Negative TTL should delete the item
        $cache->set('negative_ttl', 'new_value', -1);
        $this->assertFalse($cache->has('negative_ttl'));
    }

    public function testPsr16NullTtlMeansForever(): void
    {
        $cache = Cache::instance();
        $cache->set('null_ttl', 'forever_value', null);
        
        sleep(2);
        $this->assertTrue($cache->has('null_ttl'));
        $this->assertEquals('forever_value', $cache->get('null_ttl'));
    }

    public function testPsr16InvalidKeyThrowsException(): void
    {
        $cache = Cache::instance();
        
        $this->expectException(InvalidCacheKeyException::class);
        $cache->get('invalid{key}');
    }

    public function testPsr16InvalidKeyWithReservedCharacters(): void
    {
        $invalidKeys = [
            'key{with}braces',
            'key(with)parens',
            'key/with/slashes',
            'key\\with\\backslashes',
            'key@with@at',
            'key:with:colons',
        ];
        
        $cache = Cache::instance();
        $exceptionCount = 0;
        
        foreach ($invalidKeys as $key) {
            try {
                $cache->get($key);
            } catch (InvalidCacheKeyException) {
                $exceptionCount++;
            }
        }
        
        $this->assertEquals(count($invalidKeys), $exceptionCount);
    }

    public function testPsr16EmptyKeyThrowsException(): void
    {
        $cache = Cache::instance();
        
        $this->expectException(InvalidCacheKeyException::class);
        $cache->get('');
    }

    // ============ Legacy Static API Tests ============

    public function testPutAndGet(): void
    {
        Cache::put('name', 'John');
        $this->assertEquals('John', Cache::pull('name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Cache::pull('nonexistent', 'default'));
        $this->assertNull(Cache::pull('nonexistent'));
    }

    public function testExists(): void
    {
        $this->assertFalse(Cache::exists('key'));
        Cache::put('key', 'value');
        $this->assertTrue(Cache::exists('key'));
    }

    public function testForget(): void
    {
        Cache::put('key', 'value');
        $this->assertTrue(Cache::exists('key'));

        Cache::forget('key');
        $this->assertFalse(Cache::exists('key'));
    }

    public function testFlush(): void
    {
        Cache::put('a', 1);
        Cache::put('b', 2);
        
        Cache::flush();
        
        $this->assertFalse(Cache::exists('a'));
        $this->assertFalse(Cache::exists('b'));
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

    public function testRememberWithDateInterval(): void
    {
        $count = 0;
        $callback = function () use (&$count) {
            $count++;
            return 'computed';
        };

        $result = Cache::remember('interval_remember', new DateInterval('PT1H'), $callback);
        $this->assertEquals('computed', $result);
        $this->assertEquals(1, $count);
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
        $this->assertEquals('value', Cache::pull('expires'));

        // Wait for expiration
        sleep(2);
        $this->assertNull(Cache::pull('expires'));
    }

    // ============ Additional Behavioral Tests ============

    public function testNullTtlMeansForeverStaticApi(): void
    {
        Cache::put('forever_null', 'value', null);
        
        sleep(2);
        $this->assertTrue(Cache::exists('forever_null'));
        $this->assertEquals('value', Cache::pull('forever_null'));
    }

    public function testCacheDirectoryCreated(): void
    {
        $tempDir = sys_get_temp_dir() . '/intent_test_' . uniqid();
        Cache::reset();
        Config::set('path.cache', $tempDir);
        
        Cache::put('creates_dir', 'value');
        
        $this->assertTrue(Cache::exists('creates_dir'));
        $this->assertEquals('value', Cache::pull('creates_dir'));
        
        // Clean up
        Cache::flush();
        @rmdir($tempDir);
    }

    public function testStoresArrays(): void
    {
        $data = ['foo' => 'bar', 'baz' => [1, 2, 3]];
        Cache::put('array', $data);
        $this->assertEquals($data, Cache::pull('array'));
    }

    public function testStoresIntegers(): void
    {
        Cache::put('int', 42);
        $this->assertSame(42, Cache::pull('int'));
    }

    public function testStoresBooleans(): void
    {
        Cache::put('bool_true', true);
        Cache::put('bool_false', false);
        
        $this->assertTrue(Cache::pull('bool_true'));
        $this->assertFalse(Cache::pull('bool_false'));
    }

    public function testStoresNull(): void
    {
        Cache::put('null_value', null);
        $this->assertNull(Cache::pull('null_value'));
        $this->assertTrue(Cache::exists('null_value'));
    }

    public function testForeverMethod(): void
    {
        Cache::forever('permanent', 'forever_value');
        
        sleep(2);
        $this->assertTrue(Cache::exists('permanent'));
        $this->assertEquals('forever_value', Cache::pull('permanent'));
    }

    public function testExpiredReturnsDefault(): void
    {
        Cache::put('temp', 'value', 1);
        sleep(2);
        
        $result = Cache::pull('temp', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    public function testExpiredItemsAreForgotten(): void
    {
        Cache::put('expire_forget', 'value', 1);
        sleep(2);
        
        // First get should trigger forget
        Cache::pull('expire_forget');
        
        // exists() should return false after forget 
        $this->assertFalse(Cache::exists('expire_forget'));
    }

    public function testPutReturnsBool(): void
    {
        $result = Cache::put('bool_return', 'value');
        $this->assertTrue($result);
    }

    public function testForgetReturnsBool(): void
    {
        Cache::put('to_forget', 'value');
        $this->assertTrue(Cache::forget('to_forget'));
    }

    public function testFlushReturnsBool(): void
    {
        Cache::put('a', 1);
        $this->assertTrue(Cache::flush());
    }
}
