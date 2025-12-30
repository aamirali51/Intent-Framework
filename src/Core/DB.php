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
}
