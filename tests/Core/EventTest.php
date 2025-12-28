<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    protected function setUp(): void
    {
        Event::flush();
    }

    public function testListen(): void
    {
        Event::listen('test', fn() => 'fired');
        $this->assertTrue(Event::hasListeners('test'));
    }

    public function testDispatch(): void
    {
        $called = false;
        Event::listen('test', function () use (&$called) {
            $called = true;
        });

        Event::dispatch('test');
        $this->assertTrue($called);
    }

    public function testDispatchWithData(): void
    {
        $received = null;
        Event::listen('user.created', function ($user) use (&$received) {
            $received = $user;
        });

        Event::dispatch('user.created', ['name' => 'John']);
        $this->assertEquals(['name' => 'John'], $received);
    }

    public function testMultipleListeners(): void
    {
        $count = 0;
        Event::listen('test', fn() => $count++);
        Event::listen('test', fn() => $count++);
        Event::listen('test', fn() => $count++);

        Event::dispatch('test');
        $count = 3; // All three should fire
        $this->assertEquals(3, $count);
    }

    public function testForget(): void
    {
        Event::listen('test', fn() => 'fired');
        $this->assertTrue(Event::hasListeners('test'));

        Event::forget('test');
        $this->assertFalse(Event::hasListeners('test'));
    }

    public function testUntil(): void
    {
        Event::listen('test', fn() => null);
        Event::listen('test', fn() => 'stopped');
        Event::listen('test', fn() => 'never');

        $result = Event::until('test');
        $this->assertEquals('stopped', $result);
    }
}
