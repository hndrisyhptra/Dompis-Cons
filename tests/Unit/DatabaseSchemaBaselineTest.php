<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DatabaseSchemaBaselineTest extends TestCase
{
    private const EXPECTED_PENDING_MIGRATIONS = [
        '2026_08_31_120000_add_performance_indexes_for_pm_dashboard',
        '2026_09_07_090000_drop_role_enum_from_users_table',
    ];

    private const ALLOWED_INSERT_TABLES = [
        'customers',
        'kendala_categories',
        'migrations',
        'permit_categories',
        'project_stages',
        'roles',
    ];

    public function test_baseline_contains_the_complete_audited_schema(): void
    {
        $sql = $this->baselineSql();

        preg_match_all('/^CREATE TABLE `([^`]+)`/m', $sql, $matches);
        $tables = array_values(array_unique($matches[1]));

        $this->assertCount(54, $tables);

        foreach (['projects', 'lops', 'boq_items', 'evidences', 'users', 'project_stages'] as $table) {
            $this->assertContains($table, $tables);
        }

        $this->assertDoesNotMatchRegularExpression('/\sAUTO_INCREMENT=\d+/i', $sql);
    }

    public function test_baseline_contains_no_transactional_or_user_data(): void
    {
        $sql = $this->baselineSql();

        preg_match_all('/^INSERT INTO `([^`]+)`/m', $sql, $matches);
        $insertTables = array_values(array_unique($matches[1]));
        sort($insertTables);

        $allowedTables = self::ALLOWED_INSERT_TABLES;
        sort($allowedTables);

        $this->assertSame($allowedTables, $insertTables);
        $this->assertSame(10, $this->insertCount($sql, 'roles'));
        $this->assertSame(6, $this->insertCount($sql, 'customers'));
        $this->assertSame(13, $this->insertCount($sql, 'project_stages'));
        $this->assertSame(32, $this->insertCount($sql, 'kendala_categories'));
        $this->assertSame(12, $this->insertCount($sql, 'permit_categories'));
    }

    public function test_only_reviewed_migrations_remain_pending_after_baseline(): void
    {
        $sql = $this->baselineSql();

        preg_match_all(
            '/^INSERT INTO `migrations` .* VALUES \(\d+,\'([^\']+)\',\d+\);$/m',
            $sql,
            $matches,
        );

        $baselineMigrations = array_values(array_unique($matches[1]));
        $repositoryMigrations = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            glob(dirname(__DIR__, 2).'/database/migrations/*.php') ?: [],
        );

        $pending = array_values(array_diff($repositoryMigrations, $baselineMigrations));
        sort($pending);

        $expectedPending = self::EXPECTED_PENDING_MIGRATIONS;
        sort($expectedPending);

        $this->assertCount(54, $baselineMigrations);
        $this->assertSame($expectedPending, $pending);
    }

    private function baselineSql(): string
    {
        $path = dirname(__DIR__, 2).'/database/schema/mysql-schema.sql';

        $this->assertFileExists($path);

        $sql = file_get_contents($path);

        $this->assertIsString($sql);

        return $sql;
    }

    private function insertCount(string $sql, string $table): int
    {
        return preg_match_all('/^INSERT INTO `'.preg_quote($table, '/').'`/m', $sql);
    }
}
