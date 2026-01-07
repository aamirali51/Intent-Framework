<?php

declare(strict_types=1);

namespace Core\Contracts;

use Core\QueryBuilder;

/**
 * Database proxy interface for type-safe helper access.
 * 
 * Provides PHPStan Level 9 compatibility for db() helper.
 */
interface DatabaseProxy
{
    /**
     * Start a query on a table.
     */
    public function table(string $table): QueryBuilder;

    /**
     * Execute raw SQL query.
     * 
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function raw(string $sql, array $bindings = []): array;

    /**
     * Get the PDO connection.
     */
    public function connection(): \PDO;

    /**
     * Execute a callback within a database transaction.
     */
    public function transaction(callable $callback): mixed;

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current transaction.
     */
    public function commit(): bool;

    /**
     * Rollback the current transaction.
     */
    public function rollback(): bool;
}
