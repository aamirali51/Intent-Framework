<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    protected function setUp(): void
    {
        Package::reset();
    }

    protected function tearDown(): void
    {
        Package::reset();
    }

    public function testAllReturnsArray(): void
    {
        $packages = Package::all();
        $this->assertIsArray($packages);
    }

    public function testHasReturnsFalseForUnknownPackage(): void
    {
        $this->assertFalse(Package::has('unknown/package'));
    }

    public function testGetReturnsNullForUnknownPackage(): void
    {
        $this->assertNull(Package::get('unknown/package'));
    }

    public function testReset(): void
    {
        Package::boot();
        Package::reset();
        $this->assertEmpty(Package::all());
    }

    public function testBootCanBeCalledMultipleTimes(): void
    {
        Package::boot();
        Package::boot();
        // Should not throw
        $this->assertTrue(true);
    }

    public function testRegisterWithNonExistentClass(): void
    {
        // Should not throw, just skip
        Package::register('NonExistent\\Provider');
        $this->assertTrue(true);
    }
}
