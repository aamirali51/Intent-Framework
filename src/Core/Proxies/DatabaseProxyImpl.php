<?php

declare(strict_types=1);

namespace Core\Proxies;

use Core\Contracts\DatabaseProxy;
use Core\DB;
use Core\QueryBuilder;

/**
 * Database proxy implementation.
 * 
 * Wraps static DB class for instance-style access via Registry.
 */
final class DatabaseProxyImpl implements DatabaseProxy
{
    public function table(string $table): QueryBuilder
    {
        return DB::table($table);
    }

    /**
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function raw(string $sql, array $bindings = []): array
    {
        return DB::raw($sql, $bindings);
    }

    public function connection(): \PDO
    {
        return DB::connection();
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    public function beginTransaction(): bool
    {
        return DB::beginTransaction();
    }

    public function commit(): bool
    {
        return DB::commit();
    }

    public function rollback(): bool
    {
        return DB::rollback();
    }
}
