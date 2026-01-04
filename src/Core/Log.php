<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple file-based logger (PSR-3 compatible).
 * 
 * Logs messages to storage/logs/ directory.
 * Supports all PSR-3 log levels.
 * 
 * Usage:
 *   Log::info('User logged in', ['user_id' => 123]);
 *   Log::error('Payment failed', ['order_id' => 456]);
 * 
 * PSR-3 compatible - can be wrapped with Psr\Log\LoggerInterface
 */
final class Log
{
    private const LEVELS = [
        'emergency', 'alert', 'critical', 'error', 
        'warning', 'notice', 'info', 'debug'
    ];

    /**
     * System is unusable.
     * 
     * @param array<string, mixed> $context
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     * 
     * @param array<string, mixed> $context
     */
    public static function alert(string $message, array $context = []): void
    {
        self::log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     * 
     * @param array<string, mixed> $context
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('critical', $message, $context);
    }

    /**
     * Runtime errors.
     * 
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     * 
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     * 
     * @param array<string, mixed> $context
     */
    public static function notice(string $message, array $context = []): void
    {
        self::log('notice', $message, $context);
    }

    /**
     * Interesting events.
     * 
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     * 
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    /**
     * Write a log entry.
     * 
     * @param array<string, mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        
        if (!in_array($level, self::LEVELS, true)) {
            $level = 'info';
        }

        $logDir = self::getLogDirectory();
        $logFile = $logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';

        // Ensure log directory exists
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Format log entry
        $entry = self::formatEntry($level, $message, $context);

        // Append to file
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get the log directory path.
     */
    private static function getLogDirectory(): string
    {
        return (defined('BASE_PATH') ? BASE_PATH : getcwd()) . '/storage/logs';
    }

    /**
     * Format a log entry.
     * 
     * @param array<string, mixed> $context
     */
    private static function formatEntry(string $level, string $message, array $context): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        $contextString = '';
        if (!empty($context)) {
            $contextString = ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        return "[{$timestamp}] [{$levelUpper}] {$message}{$contextString}" . PHP_EOL;
    }

    /**
     * Read the latest log file contents.
     */
    public static function read(?string $date = null): string
    {
        $date = $date ?? date('Y-m-d');
        $logFile = self::getLogDirectory() . DIRECTORY_SEPARATOR . $date . '.log';

        if (!file_exists($logFile)) {
            return '';
        }

        return file_get_contents($logFile) ?: '';
    }

    /**
     * Clear all log files.
     */
    public static function clear(): void
    {
        $logDir = self::getLogDirectory();
        
        if (!is_dir($logDir)) {
            return;
        }

        $files = glob($logDir . DIRECTORY_SEPARATOR . '*.log');
        
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Get available log dates.
     * 
     * @return array<int, string>
     */
    public static function dates(): array
    {
        $logDir = self::getLogDirectory();
        
        if (!is_dir($logDir)) {
            return [];
        }

        $files = glob($logDir . DIRECTORY_SEPARATOR . '*.log');
        
        if ($files === false) {
            return [];
        }

        $dates = [];

        foreach ($files as $file) {
            $dates[] = basename($file, '.log');
        }

        rsort($dates);
        return $dates;
    }
}
