<?php

namespace Tests\Unit;

use App\Services\MigrationLedgerReconciler;
use PHPUnit\Framework\TestCase;

class MigrationLedgerReconcilerTest extends TestCase
{
    public function test_reconciliation_targets_match_the_migrations_adopted_by_the_baseline(): void
    {
        $sql = file_get_contents(dirname(__DIR__, 2).'/database/schema/mysql-schema.sql');

        $this->assertIsString($sql);

        foreach (MigrationLedgerReconciler::migrationNames() as $migration) {
            $this->assertStringContainsString(
                'INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ',
                $sql,
            );
            $this->assertStringContainsString("'{$migration}'", $sql);
        }

        $this->assertCount(10, MigrationLedgerReconciler::migrationNames());
    }
}
