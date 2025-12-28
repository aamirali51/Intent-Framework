<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        // Start session for tests
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSetAndGet(): void
    {
        Session::set('name', 'John');
        $this->assertEquals('John', Session::get('name'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', Session::get('nonexistent', 'default'));
        $this->assertNull(Session::get('nonexistent'));
    }

    public function testHas(): void
    {
        $this->assertFalse(Session::has('key'));
        Session::set('key', 'value');
        $this->assertTrue(Session::has('key'));
    }

    public function testForget(): void
    {
        Session::set('key', 'value');
        $this->assertTrue(Session::has('key'));
        
        Session::forget('key');
        $this->assertFalse(Session::has('key'));
    }

    public function testAll(): void
    {
        Session::set('a', 1);
        Session::set('b', 2);
        
        $all = Session::all();
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
    }

    public function testClear(): void
    {
        Session::set('a', 1);
        Session::set('b', 2);
        
        Session::clear();
        
        $this->assertFalse(Session::has('a'));
        $this->assertFalse(Session::has('b'));
    }

    public function testPush(): void
    {
        Session::push('items', 'a');
        Session::push('items', 'b');
        
        $items = Session::get('items');
        $this->assertEquals(['a', 'b'], $items);
    }

    public function testIncrement(): void
    {
        Session::set('count', 5);
        
        $new = Session::increment('count');
        $this->assertEquals(6, $new);
        
        $new = Session::increment('count', 3);
        $this->assertEquals(9, $new);
    }

    public function testDecrement(): void
    {
        Session::set('count', 10);
        
        $new = Session::decrement('count');
        $this->assertEquals(9, $new);
        
        $new = Session::decrement('count', 4);
        $this->assertEquals(5, $new);
    }

    public function testFlash(): void
    {
        // Set flash message
        Session::flash('success', 'Saved!');
        
        // Simulate new request (age flash data)
        $_SESSION['_flash_old'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        
        // Flash should now be readable
        $this->assertEquals('Saved!', Session::getFlash('success'));
    }

    public function testGetFlashes(): void
    {
        Session::flash('success', 'Done');
        Session::flash('error', 'Oops');
        
        // Simulate request cycle
        $_SESSION['_flash_old'] = $_SESSION['_flash'] ?? [];
        $_SESSION['_flash'] = [];
        
        $flashes = Session::getFlashes();
        $this->assertArrayHasKey('success', $flashes);
        $this->assertArrayHasKey('error', $flashes);
    }
}
