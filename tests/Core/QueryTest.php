<?php

declare(strict_types=1);

namespace Tests\Core;

use Core\Query;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Query class.
 * 
 * Query class contributed by @UniForceMusic
 */
class QueryTest extends TestCase
{
    public function testToSqlWithNamedParameters(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE id = :id AND name = :name',
            [':id' => 1, ':name' => 'John']
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('1', $sql);
        $this->assertStringContainsString('John', $sql);
        $this->assertStringNotContainsString(':id', $sql);
        $this->assertStringNotContainsString(':name', $sql);
    }

    public function testToSqlWithNullValue(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE deleted_at = :deleted',
            [':deleted' => null]
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('NULL', $sql);
    }

    public function testToSqlWithBooleanValue(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE active = :active',
            [':active' => true]
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('1', $sql);
    }

    public function testToSqlWithFalseValue(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE active = :active',
            [':active' => false]
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('0', $sql);
    }

    public function testToSqlWithDateTimeValue(): void
    {
        $date = new \DateTime('2024-01-15 10:30:00');
        $query = new Query(
            'SELECT * FROM users WHERE created_at = :date',
            [':date' => $date]
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('2024-01-15 10:30:00', $sql);
    }

    public function testToSqlWithIntegerValue(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE age = :age',
            [':age' => 25]
        );

        $sql = $query->toSql('mysql');

        $this->assertStringContainsString('25', $sql);
    }

    public function testMysqlUsesDoubleQuotesForStrings(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE name = :name',
            [':name' => 'John']
        );

        $sql = $query->toSql('mysql');

        // MySQL uses double quotes for string values
        $this->assertStringContainsString('"John"', $sql);
    }

    public function testSqliteUsesSingleQuotesForStrings(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE name = :name',
            [':name' => 'John']
        );

        $sql = $query->toSql('sqlite');

        // SQLite/PostgreSQL use single quotes for string values
        $this->assertStringContainsString("'John'", $sql);
    }

    public function testPostgresUsesSingleQuotesForStrings(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE name = :name',
            [':name' => 'John']
        );

        $sql = $query->toSql('pgsql');

        // PostgreSQL uses single quotes for string values
        $this->assertStringContainsString("'John'", $sql);
    }

    public function testStringWithQuotesIsEscaped(): void
    {
        $query = new Query(
            'SELECT * FROM users WHERE name = :name',
            [':name' => "O'Brien"]
        );

        $sql = $query->toSql('sqlite');

        // Single quotes should be doubled
        $this->assertStringContainsString("''", $sql);
    }

    public function testPublicPropertiesAccessible(): void
    {
        $query = new Query('SELECT 1', [':id' => 1]);

        $this->assertSame('SELECT 1', $query->sql);
        $this->assertSame([':id' => 1], $query->params);
    }
}
