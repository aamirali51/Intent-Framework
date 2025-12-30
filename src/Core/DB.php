<?php

declare(strict_types=1);

namespace Core;

/**
 * Database connection manager and static facade.
 * 
 * Provides static access to query builder and manages PDO connection.
 * 
 * Usage:
 *   DB::table('users')->where('id', 1)->first();
 *   DB::raw('SELECT * FROM users WHERE id = ?', [1]);
 *   $pdo = DB::connection();
 */
final class DB
{
    private static ?\PDO $pdo = null;

    /**
     * Get or create PDO connection.
     */
    public static function connection(): \PDO
    {
        if (self::$pdo === null) {
            $driver = Config::get('db.driver', 'mysql');
            $host = Config::get('db.host', 'localhost');
            $port = Config::get('db.port', 3306);
            $name = Config::get('db.name', 'intent');
            $user = Config::get('db.user', 'root');
            $pass = Config::get('db.pass', '');

            // Build driver-specific DSN
            $dsn = match($driver) {
                'sqlite' => "sqlite:{$name}",
                'pgsql' => "pgsql:host={$host};port={$port};dbname={$name}",
                'mysql' => "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                default => "{$driver}:host={$host};port={$port};dbname={$name}"
            };

            self::$pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Apply SQLite performance optimizations (PRAGMAs)
            if ($driver === 'sqlite') {
                self::$pdo->exec('PRAGMA journal_mode = WAL');      // Write-Ahead Logging for better concurrency
                self::$pdo->exec('PRAGMA foreign_keys = ON');       // Enable foreign key constraints
                self::$pdo->exec('PRAGMA synchronous = NORMAL');    // Balance between safety and speed
                self::$pdo->exec('PRAGMA cache_size = -64000');     // 64MB cache
                self::$pdo->exec('PRAGMA temp_store = MEMORY');     // Store temp tables in memory
            }
        }

        return self::$pdo;
    }

    /**
     * Start a query on a table.
     * 
     * Returns a QueryBuilder instance for fluent query construction.
     */
    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder($table);
    }

    /**
     * Execute raw SQL query.
     */
    public static function raw(string $sql, array $bindings = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Get the database driver name.
     * 
     * Used by QueryBuilder for identifier escaping.
     */
    public static function getDriverName(): string
    {
        return self::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Execute a callback within a database transaction.
     * 
     * Automatically commits on success, rolls back on exception.
     * 
     * Usage:
     *   DB::transaction(function() {
     *       DB::table('users')->insert(['name' => 'John']);
     *       DB::table('orders')->insert(['user_id' => 1]);
     *   });
     * 
     * @param callable $callback Function to execute within transaction
     * @return mixed Return value from callback
     * @throws \Throwable Re-throws any exception after rollback
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Begin a database transaction.
     */
    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    /**
     * Rollback the current transaction.
     */
    public static function rollback(): bool
    {
        return self::connection()->rollBack();
    }
}
