<?php

declare(strict_types=1);

namespace Core;

/**
 * DEV-only schema inference.
 * 
 * SAFETY RULES:
 * 1. Only enabled when app.debug = true
 * 2. Never drops columns (additive only)
 * 3. Deterministic type mapping
 * 4. Writes inferred schema to cache file
 * 5. In production, tables must pre-exist
 * 
 * This is NOT a migration system. It's a dev convenience.
 */
final class Schema
{
    /** @var array<string, array{created_at?: string, modified_at?: string, refreshed_at?: string, columns: array<string, array{type: string, php_type?: string}>}> */
    private static array $schemaCache = [];
    private static bool $loaded = false;

    /**
     * Type mapping: PHP type → SQL type.
     * Deterministic, no guessing.
     */
    private const TYPE_MAP = [
        'string' => 'VARCHAR(255)',
        'integer' => 'INT',
        'double' => 'DOUBLE',
        'boolean' => 'TINYINT(1)',
        'NULL' => 'VARCHAR(255)',
        'array' => 'JSON',
        'object' => 'JSON',
    ];

    /**
     * Ensure a table exists with the given columns.
     * 
     * REQUIRES: app.debug=true AND feature.schema=true
     * In DEV mode: Creates table/adds columns if missing.
     * In PROD mode: Throws exception if table doesn't exist.
     * 
     * @param string $table Table name
     * @param array<string, mixed> $data Sample row data for type inference
     */
    public static function ensure(string $table, array $data): void
    {
        $debug = Config::get('app.debug', false);
        $schemaEnabled = Config::get('feature.schema', true);

        if (!$debug || !$schemaEnabled) {
            // Production or feature disabled: just verify table exists
            if (!self::tableExists($table)) {
                throw new \RuntimeException(
                    "Table '{$table}' does not exist. Run migrations or create schema manually."
                );
            }
            return;
        }

        // Dev mode with feature enabled: auto-create/alter
        self::loadCache();

        if (!self::tableExists($table)) {
            self::createTable($table, $data);
        } else {
            self::addMissingColumns($table, $data);
        }

        self::saveCache();
    }

    /**
     * Refresh schema cache by re-reading from database.
     * Use when schema was changed outside PHP (manual SQL, etc.)
     */
    public static function refresh(string $table): void
    {
        self::loadCache();
        
        if (self::tableExists($table)) {
            $columns = self::getColumns($table);
            self::$schemaCache[$table] = [
                'refreshed_at' => date('c'),
                'columns' => array_fill_keys($columns, ['type' => 'unknown']),
            ];
            self::saveCache();
            self::log("Refreshed schema for: {$table}");
        }
    }

    /**
     * Check if a table exists.
     */
    public static function tableExists(string $table): bool
    {
        try {
            $stmt = DB::connection()->prepare(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?"
            );
            $stmt->execute([Config::get('db.name'), $table]);
            return $stmt->fetch() !== false;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Get existing columns for a table.
     * 
     * @return array<int, string>
     */
    public static function getColumns(string $table): array
    {
        $stmt = DB::connection()->prepare(
            "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = ? AND table_name = ?"
        );
        $stmt->execute([Config::get('db.name'), $table]);
        /** @var array<int, string> $columns */
        $columns = array_column($stmt->fetchAll(), 'COLUMN_NAME');
        return $columns;
    }

    /**
     * Create a new table based on sample data.
     * 
     * @param array<string, mixed> $data
     */
    private static function createTable(string $table, array $data): void
    {
        $columns = ['id INT AUTO_INCREMENT PRIMARY KEY'];

        foreach ($data as $column => $value) {
            if ($column === 'id') {
                continue; // Skip id, we define it ourselves
            }

            $sqlType = self::inferType($value);
            $nullable = $value === null ? ' NULL' : ' NOT NULL';
            $columns[] = "`{$column}` {$sqlType}{$nullable}";
        }

        // Add timestamps by default
        if (!isset($data['created_at'])) {
            $columns[] = '`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP';
        }
        if (!isset($data['updated_at'])) {
            $columns[] = '`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        }

        $columnsSql = implode(",\n    ", $columns);
        $sql = "CREATE TABLE `{$table}` (\n    {$columnsSql}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        DB::connection()->exec($sql);

        // Cache the schema
        self::$schemaCache[$table] = [
            'created_at' => date('c'),
            'columns' => self::inferColumns($data),
        ];

        self::log("Created table: {$table}");
    }

    /**
     * Add missing columns to an existing table.
     * NEVER drops columns.
     * 
     * @param array<string, mixed> $data
     */
    private static function addMissingColumns(string $table, array $data): void
    {
        $existingColumns = self::getColumns($table);
        $added = [];

        foreach ($data as $column => $value) {
            if ($column === 'id') {
                continue;
            }

            if (!in_array($column, $existingColumns, true)) {
                $sqlType = self::inferType($value);
                $nullable = ' NULL'; // New columns must be nullable or have default

                $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$sqlType}{$nullable}";
                DB::connection()->exec($sql);
                $added[] = $column;
            }
        }

        if (!empty($added)) {
            // Update cache
            if (!isset(self::$schemaCache[$table])) {
                self::$schemaCache[$table] = ['columns' => []];
            }
            self::$schemaCache[$table]['modified_at'] = date('c');
            self::$schemaCache[$table]['columns'] = array_merge(
                self::$schemaCache[$table]['columns'] ?? [],
                self::inferColumns(array_intersect_key($data, array_flip($added)))
            );

            self::log("Added columns to {$table}: " . implode(', ', $added));
        }
    }

    /**
     * Infer SQL type from PHP value.
     */
    private static function inferType(mixed $value): string
    {
        if ($value instanceof \DateTime || $value instanceof \DateTimeImmutable) {
            return 'DATETIME';
        }

        $phpType = gettype($value);
        return self::TYPE_MAP[$phpType] ?? 'VARCHAR(255)';
    }

    /**
     * Infer column definitions from data.
     * 
     * @param array<string, mixed> $data
     * @return array<string, array{type: string, php_type: string}>
     */
    private static function inferColumns(array $data): array
    {
        $columns = [];
        foreach ($data as $column => $value) {
            $columns[$column] = [
                'type' => self::inferType($value),
                'php_type' => gettype($value),
            ];
        }
        return $columns;
    }

    /**
     * Load schema cache from file.
     */
    private static function loadCache(): void
    {
        if (self::$loaded) {
            return;
        }

        $cacheFile = self::getCacheFile();
        if (file_exists($cacheFile)) {
            self::$schemaCache = require $cacheFile;
        }

        self::$loaded = true;
    }

    /**
     * Save schema cache to file.
     */
    private static function saveCache(): void
    {
        $cacheFile = self::getCacheFile();
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $export = var_export(self::$schemaCache, true);
        $content = "<?php\n\n// Auto-generated schema cache\n// Generated: " . date('c') . "\n\nreturn {$export};\n";

        file_put_contents($cacheFile, $content);
    }

    /**
     * Get cache file path.
     */
    private static function getCacheFile(): string
    {
        return BASE_PATH . '/storage/cache/schema.php';
    }

    /**
     * Log schema changes (dev only).
     */
    private static function log(string $message): void
    {
        if (Config::get('app.debug', false)) {
            error_log("[Schema] {$message}");
        }
    }

    /**
     * Get cached schema info.
     * 
     * @return array<string, array{created_at?: string, modified_at?: string, refreshed_at?: string, columns: array<string, array{type: string, php_type?: string}>}>
     */
    public static function getCached(): array
    {
        self::loadCache();
        return self::$schemaCache;
    }

    /**
     * Clear schema cache.
     */
    public static function clearCache(): void
    {
        $cacheFile = self::getCacheFile();
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
        self::$schemaCache = [];
        self::$loaded = false;
    }
}
