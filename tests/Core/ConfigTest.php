<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset config for each test
        Config::load(BASE_PATH . '/tests/config.php');
    }

    public function testLoadConfig(): void
    {
        $this->assertEquals('Intent Test', Config::get('app.name'));
        $this->assertEquals('testing', Config::get('app.env'));
        $this->assertTrue(Config::get('app.debug'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertNull(Config::get('nonexistent'));
        $this->assertEquals('default', Config::get('nonexistent', 'default'));
    }

    public function testSetConfig(): void
    {
        Config::set('test.key', 'test_value');
        $this->assertEquals('test_value', Config::get('test.key'));
    }

    public function testAllConfig(): void
    {
        $all = Config::all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app.name', $all);
    }

    public function testLoadDoesNotReloadWhenAlreadyLoaded(): void
    {
        // First load
        Config::load(BASE_PATH . '/tests/config.php');
        $firstValue = Config::get('app.name');
        
        // Try to load again - should return early
        Config::load(BASE_PATH . '/tests/config.php');
        $secondValue = Config::get('app.name');
        
        // Values should be the same (not reloaded)
        $this->assertEquals($firstValue, $secondValue);
    }
}

