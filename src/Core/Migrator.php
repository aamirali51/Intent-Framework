<?php

declare(strict_types=1);

namespace Core;

/**
 * Migration runner.
 * 
 * Usage:
 *   php intent migrate           # Run pending migrations
 *   php intent migrate:rollback  # Rollback last batch
 *   php intent make:migration create_users_table
 */
final class Migrator
{
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(?string $path = null)
    {
        $this->migrationsPath = $path ?? (defined('BASE_PATH') ? BASE_PATH : getcwd()) . '/database/migrations';
    }

    /**
     * Run all pending migrations.
     * 
     * @return array<int, string>
     */
    public function run(): array
    {
        $this->ensureMigrationsTable();
        
        $ran = [];
        $batch = $this->getNextBatchNumber();
        $pending = $this->getPendingMigrations();

        foreach ($pending as $file) {
            $migration = $this->resolve($file);
            $migration->up();
            
            $this->recordMigration($file, $batch);
            $ran[] = $file;
        }

        return $ran;
    }

    /**
     * Rollback the last batch of migrations.
     * 
     * @return array<int, string>
     */
    public function rollback(): array
    {
        $this->ensureMigrationsTable();
        
        $rolledBack = [];
        $lastBatch = $this->getLastBatch();

        foreach ($lastBatch as $record) {
            /** @var string $migrationName */
            $migrationName = $record['migration'];
            $migration = $this->resolve($migrationName);
            $migration->down();
            
            $this->deleteMigration($migrationName);
            $rolledBack[] = $migrationName;
        }

        return $rolledBack;
    }

    /**
     * Get pending migrations.
     * 
     * @return array<int, string>
     */
    public function getPendingMigrations(): array
    {
        $files = $this->getMigrationFiles();
        $ran = $this->getRanMigrations();

        return array_values(array_diff($files, $ran));
    }

    /**
     * Get all migration files.
     * 
     * @return array<int, string>
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        $migrations = [];

        foreach ($files ?: [] as $file) {
            $migrations[] = basename($file, '.php');
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Get migrations that have already run.
     * 
     * @return array<int, string>
     */
    private function getRanMigrations(): array
    {
        $results = DB::raw("SELECT migration FROM {$this->migrationsTable} ORDER BY batch, migration");
        /** @var array<int, string> $migrations */
        $migrations = array_column($results, 'migration');
        return $migrations;
    }

    /**
     * Resolve a migration instance from file.
     */
    private function resolve(string $file): Migration
    {
        $path = $this->migrationsPath . '/' . $file . '.php';
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Migration file not found: {$file}");
        }

        $migration = require $path;

        if (!$migration instanceof Migration) {
            throw new \RuntimeException("Migration must extend Core\\Migration: {$file}");
        }

        return $migration;
    }

    /**
     * Ensure the migrations table exists.
     */
    private function ensureMigrationsTable(): void
    {
        $driver = DB::getDriverName();
        
        $sql = match($driver) {
            'sqlite' => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL,
                batch INTEGER NOT NULL
            )",
            'pgsql' => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )",
            default => "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            )"
        };

        DB::connection()->exec($sql);
    }

    /**
     * Get the next batch number.
     */
    private function getNextBatchNumber(): int
    {
        $result = DB::raw("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        return (int) ($result[0]['max_batch'] ?? 0) + 1;
    }

    /**
     * Record a migration.
     */
    private function recordMigration(string $file, int $batch): void
    {
        DB::table($this->migrationsTable)->insert([
            'migration' => $file,
            'batch' => $batch,
        ]);
    }

    /**
     * Delete a migration record.
     */
    private function deleteMigration(string $file): void
    {
        DB::table($this->migrationsTable)->where('migration', $file)->delete();
    }

    /**
     * Get the last batch of migrations.
     * 
     * @return array<int, array<string, mixed>>
     */
    private function getLastBatch(): array
    {
        $result = DB::raw("SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}");
        $lastBatch = (int) ($result[0]['max_batch'] ?? 0);

        if ($lastBatch === 0) {
            return [];
        }

        return DB::raw(
            "SELECT migration FROM {$this->migrationsTable} WHERE batch = ? ORDER BY migration DESC",
            ['batch' => $lastBatch]
        );
    }

    /**
     * Create a new migration file.
     */
    public static function create(string $name): string
    {
        $path = (defined('BASE_PATH') ? BASE_PATH : getcwd()) . '/database/migrations';
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.php";
        $filepath = "{$path}/{$filename}";

        $template = <<<'PHP'
<?php

declare(strict_types=1);

use Core\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->execute("
            -- Your SQL here
        ");
    }

    public function down(): void
    {
        $this->execute("
            -- Reverse your SQL here
        ");
    }
};
PHP;

        file_put_contents($filepath, $template);
        return $filename;
    }
}
