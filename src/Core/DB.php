<?php

declare(strict_types=1);

namespace Core;

/**
 * Minimal database layer.
 * 
 * This is a query builder, NOT an ORM.
 * - Returns arrays, not objects
 * - No entity tracking
 * - No relationships
 * - No migrations
 * 
 * Usage:
 *   DB::table('users')->where('id', 1)->first();
 *   DB::table('users')->insert(['name' => 'John']);
 */
final class DB
{
    private static ?\PDO $pdo = null;

    private string $table;
    private array $wheres = [];
    private array $bindings = [];
    private array $orderBy = [];
    private ?int $limitValue = null;
    private ?int $offsetValue = null;
    private array $selectColumns = ['*'];

    /**
     * Private constructor - use DB::table() to create instances.
     */
    private function __construct(string $table)
    {
        $this->table = $table;
    }

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

            $dsn = "{$driver}:host={$host};port={$port};dbname={$name};charset=utf8mb4";

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
     */
    public static function table(string $table): self
    {
        return new self($table);
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
     * Select specific columns.
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->selectColumns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add a where clause.
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            // Two arguments: where('id', 1) means where id = 1
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            // Three arguments: where('age', '>', 18)
            $operator = $operatorOrValue;
        }

        $this->wheres[] = "{$column} {$operator} ?";
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add a where IN clause.
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = "{$column} IN ({$placeholders})";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a where NULL clause.
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = "{$column} IS NULL";
        return $this;
    }

    /**
     * Add a where NOT NULL clause.
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "{$column} IS NOT NULL";
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Limit the number of results.
     */
    public function limit(int $limit): self
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Offset the results.
     */
    public function offset(int $offset): self
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Get all matching rows.
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    /**
     * Get the first matching row.
     */
    public function first(): ?array
    {
        $this->limitValue = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find a row by primary key.
     */
    public function find(int|string $id, string $primaryKey = 'id'): ?array
    {
        return $this->where($primaryKey, $id)->first();
    }

    /**
     * Count matching rows.
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = self::connection()->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch();
        return (int) ($result['count'] ?? 0);
    }

    /**
     * Check if any rows exist.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert a new row.
     * 
     * @return int|string Last insert ID
     */
    public function insert(array $data): int|string
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = self::connection()->prepare($sql);
        $stmt->execute(array_values($data));

        return self::connection()->lastInsertId();
    }

    /**
     * Insert multiple rows.
     */
    public function insertMany(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $columns = array_keys($rows[0]);
        $columnsList = implode(', ', $columns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $sql = "INSERT INTO {$this->table} ({$columnsList}) VALUES {$allPlaceholders}";

        $values = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col] ?? null;
            }
        }

        $stmt = self::connection()->prepare($sql);
        $stmt->execute($values);

        return $stmt->rowCount();
    }

    /**
     * Update matching rows.
     * 
     * @return int Number of affected rows
     */
    public function update(array $data): int
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = self::connection()->prepare($sql);
        $stmt->execute(array_merge($values, $this->bindings));

        return $stmt->rowCount();
    }

    /**
     * Delete matching rows.
     * 
     * @return int Number of deleted rows
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        $stmt = self::connection()->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->rowCount();
    }

    /**
     * Build the SELECT query.
     */
    private function buildSelectQuery(): string
    {
        $columns = implode(', ', $this->selectColumns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limitValue !== null) {
            $sql .= " LIMIT {$this->limitValue}";
        }

        if ($this->offsetValue !== null) {
            $sql .= " OFFSET {$this->offsetValue}";
        }

        return $sql;
    }
}
