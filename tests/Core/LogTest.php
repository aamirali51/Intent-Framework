<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Log;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    private string $logDir;

    protected function setUp(): void
    {
        $this->logDir = BASE_PATH . '/storage/logs';
        Log::clear();
    }

    protected function tearDown(): void
    {
        Log::clear();
    }

    public function testInfo(): void
    {
        Log::info('Test message');
        
        $content = Log::read();
        $this->assertStringContainsString('[INFO]', $content);
        $this->assertStringContainsString('Test message', $content);
    }

    public function testDebug(): void
    {
        Log::debug('Debug message');
        
        $content = Log::read();
        $this->assertStringContainsString('[DEBUG]', $content);
        $this->assertStringContainsString('Debug message', $content);
    }

    public function testWarning(): void
    {
        Log::warning('Warning message');
        
        $content = Log::read();
        $this->assertStringContainsString('[WARNING]', $content);
        $this->assertStringContainsString('Warning message', $content);
    }

    public function testError(): void
    {
        Log::error('Error message');
        
        $content = Log::read();
        $this->assertStringContainsString('[ERROR]', $content);
        $this->assertStringContainsString('Error message', $content);
    }

    public function testCritical(): void
    {
        Log::critical('Critical message');
        
        $content = Log::read();
        $this->assertStringContainsString('[CRITICAL]', $content);
        $this->assertStringContainsString('Critical message', $content);
    }

    public function testLogWithContext(): void
    {
        Log::info('User logged in', ['user_id' => 123]);
        
        $content = Log::read();
        $this->assertStringContainsString('User logged in', $content);
        $this->assertStringContainsString('user_id', $content);
        $this->assertStringContainsString('123', $content);
    }

    public function testLogWithInvalidLevel(): void
    {
        Log::log('invalid_level', 'Test message');
        
        $content = Log::read();
        // Should default to INFO
        $this->assertStringContainsString('[INFO]', $content);
    }

    public function testReadNonExistentDate(): void
    {
        $content = Log::read('1900-01-01');
        $this->assertEmpty($content);
    }

    public function testClear(): void
    {
        Log::info('Test message');
        $this->assertNotEmpty(Log::read());
        
        Log::clear();
        $this->assertEmpty(Log::read());
    }

    public function testDates(): void
    {
        Log::info('Test message');
        
        $dates = Log::dates();
        $this->assertIsArray($dates);
        $this->assertContains(date('Y-m-d'), $dates);
    }

    public function testDatesEmpty(): void
    {
        Log::clear();
        $dates = Log::dates();
        $this->assertIsArray($dates);
    }

    public function testMultipleMessages(): void
    {
        Log::info('First message');
        Log::error('Second message');
        Log::debug('Third message');
        
        $content = Log::read();
        $this->assertStringContainsString('First message', $content);
        $this->assertStringContainsString('Second message', $content);
        $this->assertStringContainsString('Third message', $content);
    }

    public function testTimestampFormat(): void
    {
        Log::info('Test');
        
        $content = Log::read();
        // Check timestamp format [YYYY-MM-DD HH:MM:SS]
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }
}
