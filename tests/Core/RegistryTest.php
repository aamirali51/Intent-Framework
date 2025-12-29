<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Registry;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    protected function setUp(): void
    {
        Registry::flush();
    }

    protected function tearDown(): void
    {
        Registry::flush();
    }

    public function testBind(): void
    {
        Registry::bind('test', fn() => 'value');
        
        $this->assertTrue(Registry::has('test'));
        $this->assertEquals('value', Registry::make('test'));
    }

    public function testBindReturnsNewInstanceEachTime(): void
    {
        $count = 0;
        Registry::bind('counter', function() use (&$count) {
            return ++$count;
        });
        
        $this->assertEquals(1, Registry::make('counter'));
        $this->assertEquals(2, Registry::make('counter'));
        $this->assertEquals(3, Registry::make('counter'));
    }

    public function testSingleton(): void
    {
        $count = 0;
        Registry::singleton('counter', function() use (&$count) {
            return ++$count;
        });
        
        $this->assertEquals(1, Registry::make('counter'));
        $this->assertEquals(1, Registry::make('counter'));
        $this->assertEquals(1, Registry::make('counter'));
    }

    public function testInstance(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';
        
        Registry::instance('myobj', $obj);
        
        $this->assertTrue(Registry::has('myobj'));
        $this->assertSame($obj, Registry::make('myobj'));
    }

    public function testHas(): void
    {
        $this->assertFalse(Registry::has('unknown'));
        
        Registry::bind('known', fn() => 'value');
        $this->assertTrue(Registry::has('known'));
    }

    public function testForget(): void
    {
        Registry::bind('temp', fn() => 'value');
        $this->assertTrue(Registry::has('temp'));
        
        Registry::forget('temp');
        $this->assertFalse(Registry::has('temp'));
    }

    public function testFlush(): void
    {
        Registry::bind('a', fn() => 1);
        Registry::bind('b', fn() => 2);
        Registry::singleton('c', fn() => 3);
        
        Registry::flush();
        
        $this->assertFalse(Registry::has('a'));
        $this->assertFalse(Registry::has('b'));
        $this->assertFalse(Registry::has('c'));
    }

    public function testKeys(): void
    {
        Registry::bind('a', fn() => 1);
        Registry::bind('b', fn() => 2);
        Registry::instance('c', new \stdClass());
        
        $keys = Registry::keys();
        
        $this->assertContains('a', $keys);
        $this->assertContains('b', $keys);
        $this->assertContains('c', $keys);
    }

    public function testMakeThrowsForUnknownBinding(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No binding found for 'unknown'");
        
        Registry::make('unknown');
    }

    public function testRebindClearsCachedInstance(): void
    {
        Registry::singleton('service', fn() => 'first');
        $this->assertEquals('first', Registry::make('service'));
        
        // Rebind
        Registry::singleton('service', fn() => 'second');
        $this->assertEquals('second', Registry::make('service'));
    }

    public function testBindWithObject(): void
    {
        Registry::bind('user', fn() => ['id' => 1, 'name' => 'John']);
        
        $user = Registry::make('user');
        $this->assertEquals(['id' => 1, 'name' => 'John'], $user);
    }
}
