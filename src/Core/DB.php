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
        $cols = is_array($columns) ? $columns : func_get_args();
        $this->selectColumns = array_map([$this, 'escapeIdentifier'], $cols);
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

        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} {$operator} ?",
            'chain' => 'AND'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add a where IN clause.
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IN ({$placeholders})",
            'chain' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a where NULL clause.
     */
    public function whereNull(string $column): self
    {
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IS NULL",
            'chain' => 'AND'
        ];
        return $this;
    }

    /**
     * Add a where NOT NULL clause.
     */
    public function whereNotNull(string $column): self
    {
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IS NOT NULL",
            'chain' => 'AND'
        ];
        return $this;
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if ($value === null) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = $operatorOrValue;
        }

        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} {$operator} ?",
            'chain' => 'OR'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add an OR WHERE IN clause.
     */
    public function orWhereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IN ({$placeholders})",
            'chain' => 'OR'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add an OR WHERE NULL clause.
     */
    public function orWhereNull(string $column): self
    {
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IS NULL",
            'chain' => 'OR'
        ];
        return $this;
    }

    /**
     * Add an OR WHERE NOT NULL clause.
     */
    public function orWhereNotNull(string $column): self
    {
        $escapedColumn = $this->escapeIdentifier($column);
        $this->wheres[] = [
            'sql' => "{$escapedColumn} IS NOT NULL",
            'chain' => 'OR'
        ];
        return $this;
    }

    /**
     * Add an ORDER BY clause.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $escapedColumn = $this->escapeIdentifier($column);
        $this->orderBy[] = "{$escapedColumn} {$direction}";
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
        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "SELECT COUNT(*) as count FROM {$escapedTable}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
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
        $columns = array_keys($data);
        $escapedColumns = array_map([$this, 'escapeIdentifier'], $columns);
        $columnsList = implode(', ', $escapedColumns);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "INSERT INTO {$escapedTable} ({$columnsList}) VALUES ({$placeholders})";
        
        $stmt = self::connection()->prepare($sql);
        
        // Bind values with proper types
        $position = 1;
        foreach ($data as $value) {
            [$castValue, $type] = $this->castValue($value);
            $stmt->bindValue($position++, $castValue, $type);
        }
        
        $stmt->execute();
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
        $escapedColumns = array_map([$this, 'escapeIdentifier'], $columns);
        $columnsList = implode(', ', $escapedColumns);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));

        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "INSERT INTO {$escapedTable} ({$columnsList}) VALUES {$allPlaceholders}";

        $stmt = self::connection()->prepare($sql);
        
        // Bind all values with proper types
        $position = 1;
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $value = $row[$col] ?? null;
                [$castValue, $type] = $this->castValue($value);
                $stmt->bindValue($position++, $castValue, $type);
            }
        }

        $stmt->execute();
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

        foreach ($data as $column => $value) {
            $escapedColumn = $this->escapeIdentifier($column);
            $sets[] = "{$escapedColumn} = ?";
        }

        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "UPDATE {$escapedTable} SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
        }

        $stmt = self::connection()->prepare($sql);
        
        // Bind SET values with proper types
        $position = 1;
        foreach ($data as $value) {
            [$castValue, $type] = $this->castValue($value);
            $stmt->bindValue($position++, $castValue, $type);
        }
        
        // Bind WHERE values
        foreach ($this->bindings as $value) {
            [$castValue, $type] = $this->castValue($value);
            $stmt->bindValue($position++, $castValue, $type);
        }
        
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Delete matching rows.
     * 
     * @return int Number of deleted rows
     */
    public function delete(): int
    {
        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "DELETE FROM {$escapedTable}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
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
        $escapedTable = $this->escapeIdentifier($this->table);
        $sql = "SELECT {$columns} FROM {$escapedTable}";

        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWhereClause();
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

    /**
     * Build WHERE clause handling AND/OR chains.
     */
    private function buildWhereClause(): string
    {
        $whereClauses = [];
        foreach ($this->wheres as $index => $where) {
            if ($index === 0) {
                $whereClauses[] = $where['sql'];
            } else {
                $whereClauses[] = $where['chain'] . ' ' . $where['sql'];
            }
        }
        return implode(' ', $whereClauses);
    }

    /**
     * Escape identifier (table/column name) based on database driver.
     * 
     * MySQL uses backticks, PostgreSQL/SQLite use double quotes.
     */
    private function escapeIdentifier(string $identifier): string
    {
        // Handle qualified identifiers (table.column)
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            return implode('.', array_map([$this, 'escapeIdentifier'], $parts));
        }

        // Don't escape wildcards or already escaped identifiers
        if ($identifier === '*' || str_starts_with($identifier, '`') || str_starts_with($identifier, '"')) {
            return $identifier;
        }

        $driver = self::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        return match($driver) {
            'mysql' => "`{$identifier}`",
            'pgsql', 'sqlite', 'sqlsrv' => "\"{$identifier}\"",
            default => "\"{$identifier}\""
        };
    }

    /**
     * Cast value to appropriate PDO type.
     * 
     * @return array [value, PDO::PARAM_*]
     */
    private function castValue(mixed $value): array
    {
        return match(true) {
            $value instanceof \DateTimeInterface => [
                $value->format('Y-m-d H:i:s'),
                \PDO::PARAM_STR
            ],
            is_bool($value) => [
                $value ? 1 : 0,
                \PDO::PARAM_INT
            ],
            is_null($value) => [
                null,
                \PDO::PARAM_NULL
            ],
            is_int($value) => [
                $value,
                \PDO::PARAM_INT
            ],
            is_float($value) => [
                $value,
                \PDO::PARAM_STR  // PDO doesn't have PARAM_FLOAT
            ],
            default => [
                (string)$value,
                \PDO::PARAM_STR
            ]
        };
    }
}
