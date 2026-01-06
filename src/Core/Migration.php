<?php

declare(strict_types=1);

namespace Core;

/**
 * Simple database migrations.
 * 
 * No schema builder - just raw SQL. Simple and powerful.
 * 
 * Usage:
 *   php intent migrate           # Run pending migrations
 *   php intent migrate:rollback  # Rollback last batch
 *   php intent make:migration create_users_table
 * 
 * Migration file example:
 *   return new class extends Migration {
 *       public function up(): void {
 *           $this->execute("CREATE TABLE users (...)");
 *       }
 *       public function down(): void {
 *           $this->execute("DROP TABLE users");
 *       }
 *   };
 */
abstract class Migration
{
    /**
     * Run the migration.
     */
    abstract public function up(): void;

    /**
     * Reverse the migration.
     */
    abstract public function down(): void;

    /**
     * Execute a SQL statement.
     */
    protected function execute(string $sql): void
    {
        DB::connection()->exec($sql);
    }

    /**
     * Execute a SQL query with bindings.
     * 
     * @param array<int|string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    protected function query(string $sql, array $bindings = []): array
    {
        return DB::raw($sql, $bindings);
    }
}
