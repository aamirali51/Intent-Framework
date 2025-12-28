<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        Container::flush();
    }

    protected function tearDown(): void
    {
        Container::flush();
    }

    public function testBind(): void
    {
        Container::bind('test', fn() => 'value');
        
        $this->assertTrue(Container::has('test'));
        $this->assertEquals('value', Container::make('test'));
    }

    public function testBindReturnsNewInstanceEachTime(): void
    {
        $count = 0;
        Container::bind('counter', function() use (&$count) {
            return ++$count;
        });
        
        $this->assertEquals(1, Container::make('counter'));
        $this->assertEquals(2, Container::make('counter'));
        $this->assertEquals(3, Container::make('counter'));
    }

    public function testSingleton(): void
    {
        $count = 0;
        Container::singleton('counter', function() use (&$count) {
            return ++$count;
        });
        
        $this->assertEquals(1, Container::make('counter'));
        $this->assertEquals(1, Container::make('counter'));
        $this->assertEquals(1, Container::make('counter'));
    }

    public function testInstance(): void
    {
        $obj = new \stdClass();
        $obj->name = 'test';
        
        Container::instance('myobj', $obj);
        
        $this->assertTrue(Container::has('myobj'));
        $this->assertSame($obj, Container::make('myobj'));
    }

    public function testHas(): void
    {
        $this->assertFalse(Container::has('unknown'));
        
        Container::bind('known', fn() => 'value');
        $this->assertTrue(Container::has('known'));
    }

    public function testForget(): void
    {
        Container::bind('temp', fn() => 'value');
        $this->assertTrue(Container::has('temp'));
        
        Container::forget('temp');
        $this->assertFalse(Container::has('temp'));
    }

    public function testFlush(): void
    {
        Container::bind('a', fn() => 1);
        Container::bind('b', fn() => 2);
        Container::singleton('c', fn() => 3);
        
        Container::flush();
        
        $this->assertFalse(Container::has('a'));
        $this->assertFalse(Container::has('b'));
        $this->assertFalse(Container::has('c'));
    }

    public function testKeys(): void
    {
        Container::bind('a', fn() => 1);
        Container::bind('b', fn() => 2);
        Container::instance('c', new \stdClass());
        
        $keys = Container::keys();
        
        $this->assertContains('a', $keys);
        $this->assertContains('b', $keys);
        $this->assertContains('c', $keys);
    }

    public function testMakeThrowsForUnknownBinding(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("No binding found for 'unknown'");
        
        Container::make('unknown');
    }

    public function testRebindClearsCachedInstance(): void
    {
        Container::singleton('service', fn() => 'first');
        $this->assertEquals('first', Container::make('service'));
        
        // Rebind
        Container::singleton('service', fn() => 'second');
        $this->assertEquals('second', Container::make('service'));
    }

    public function testBindWithObject(): void
    {
        Container::bind('user', fn() => ['id' => 1, 'name' => 'John']);
        
        $user = Container::make('user');
        $this->assertEquals(['id' => 1, 'name' => 'John'], $user);
    }
}
