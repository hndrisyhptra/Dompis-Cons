<?php

namespace App\Services;

use Illuminate\Database\Connection;
use RuntimeException;

final class MigrationLedgerReconciler
{
    /**
     * Migrations whose effects were found in the audited physical schema even
     * though their names were absent from the live migration ledger.
     *
     * @var array<string, array<string, mixed>>
     */
    private const SIGNATURES = [
        '2026_08_20_100000_create_site_surveys_table' => [
            'tables' => [
                'site_surveys' => [
                    'id_site_surveys', 'project_id', 'project_name', 'title',
                    'surveyor_id', 'status', 'notes', 'ending_site_lat',
                    'ending_site_lng', 'ending_site_name', 'kml_path',
                    'completed_at', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'site_surveys' => [
                    'site_surveys_project_id_index',
                    'site_surveys_surveyor_id_index',
                    'site_surveys_status_index',
                ],
            ],
            'foreign_keys' => [
                'site_surveys' => [
                    'site_surveys_project_id_foreign',
                    'site_surveys_surveyor_id_foreign',
                ],
            ],
        ],
        '2026_08_20_100001_create_site_survey_points_table' => [
            'tables' => [
                'site_survey_points' => [
                    'id_site_survey_points', 'site_survey_id', 'type',
                    'catuan_type', 'name', 'latitude', 'longitude', 'photo_path',
                    'notes', 'order_index', 'created_by', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'site_survey_points' => ['site_survey_points_site_survey_id_type_index'],
            ],
            'foreign_keys' => [
                'site_survey_points' => ['site_survey_points_site_survey_id_foreign'],
            ],
        ],
        '2026_08_20_100002_create_site_survey_routes_table' => [
            'tables' => [
                'site_survey_routes' => [
                    'id_site_survey_routes', 'site_survey_id', 'name', 'path',
                    'distance_meters', 'order_index', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'site_survey_routes' => ['site_survey_routes_site_survey_id_index'],
            ],
            'foreign_keys' => [
                'site_survey_routes' => ['site_survey_routes_site_survey_id_foreign'],
            ],
        ],
        '2026_08_22_000000_create_baut_generates_table' => [
            'tables' => [
                'baut_generates' => [
                    'id_baut_generate', 'pt2_lop_id', 'pt2_project_id', 'status',
                    'field_values', 'boq_snapshot', 'opm_slot_count', 'photo_slots',
                    'generated_file_path', 'generated_at', 'generated_by',
                    'updated_by', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'baut_generates' => [
                    'baut_generates_pt2_lop_id_index',
                    'baut_generates_status_index',
                    'baut_generates_pt2_project_id_foreign',
                    'baut_generates_generated_by_foreign',
                    'baut_generates_updated_by_foreign',
                ],
            ],
            'foreign_keys' => [
                'baut_generates' => [
                    'baut_generates_pt2_lop_id_foreign',
                    'baut_generates_pt2_project_id_foreign',
                    'baut_generates_generated_by_foreign',
                    'baut_generates_updated_by_foreign',
                ],
            ],
        ],
        '2026_08_24_000000_create_lact_generates_table' => [
            'tables' => [
                'lact_generates' => [
                    'id_lact_generate', 'pt2_lop_id', 'pt2_project_id', 'status',
                    'field_values', 'boq_snapshot', 'opm_slot_count', 'photo_slots',
                    'generated_file_path', 'generated_at', 'generated_by',
                    'updated_by', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'lact_generates' => [
                    'lact_generates_pt2_lop_id_index',
                    'lact_generates_status_index',
                    'lact_generates_pt2_project_id_foreign',
                    'lact_generates_generated_by_foreign',
                    'lact_generates_updated_by_foreign',
                ],
            ],
            'foreign_keys' => [
                'lact_generates' => [
                    'lact_generates_pt2_lop_id_foreign',
                    'lact_generates_pt2_project_id_foreign',
                    'lact_generates_generated_by_foreign',
                    'lact_generates_updated_by_foreign',
                ],
            ],
        ],
        '2026_08_25_000000_create_telegram_webhook_events_table' => [
            'tables' => [
                'telegram_webhook_events' => [
                    'id_tele_webhook', 'event_type', 'recipient_type',
                    'recipient_user_id', 'recipient_role', 'project_id', 'lop_id',
                    'title', 'message', 'payload', 'status', 'delivered_at',
                    'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'telegram_webhook_events' => [
                    'telegram_webhook_events_status_created_at_index',
                    'telegram_webhook_events_event_type_index',
                    'telegram_webhook_events_recipient_user_id_index',
                    'telegram_webhook_events_recipient_role_index',
                ],
            ],
        ],
        '2026_08_27_010000_create_gis_cad_exports_table' => [
            'tables' => [
                'gis_cad_exports' => [
                    'id_gis_cad_export', 'uuid', 'source_type', 'site_survey_id',
                    'project_id', 'template', 'original_file_name',
                    'uploaded_file_path', 'dataset_path', 'dxf_path', 'bom_path',
                    'disk', 'status', 'current_stage', 'points_count',
                    'polylines_count', 'utm_zone', 'error_message', 'requested_by',
                    'started_at', 'finished_at', 'created_at', 'updated_at',
                ],
            ],
            'indexes' => [
                'gis_cad_exports' => [
                    'gis_cad_exports_uuid_unique',
                    'gis_cad_exports_source_type_index',
                    'gis_cad_exports_site_survey_id_index',
                    'gis_cad_exports_project_id_index',
                    'gis_cad_exports_status_index',
                    'gis_cad_exports_requested_by_index',
                ],
            ],
            'foreign_keys' => [
                'gis_cad_exports' => [
                    'fk_gis_cad_site_survey',
                    'fk_gis_cad_project',
                    'fk_gis_cad_requested_by',
                ],
            ],
        ],
        '2026_09_01_090000_add_superadmin_and_tif_roles_to_users_table' => [
            'tables' => ['users' => ['role']],
            'enum_values' => ['users.role' => ['superadmin', 'tif']],
        ],
        '2026_09_02_090000_add_super_tif_role_to_users_table' => [
            'tables' => ['users' => ['role']],
            'enum_values' => ['users.role' => ['super_tif']],
        ],
        '2026_09_04_063000_add_push_tracking_to_telegram_webhook_events_table' => [
            'tables' => [
                'telegram_webhook_events' => ['pushed_at', 'push_attempts', 'push_error'],
            ],
        ],
    ];

    /**
     * @return list<string>
     */
    public static function migrationNames(): array
    {
        return array_keys(self::SIGNATURES);
    }

    /**
     * @return array<string, array{status: string, issues: list<string>}>
     */
    public function audit(Connection $connection): array
    {
        $this->assertSupportedConnection($connection);

        $schema = $connection->getSchemaBuilder();
        $results = [];

        foreach (self::SIGNATURES as $migration => $signature) {
            $issues = [];

            foreach ($signature['tables'] ?? [] as $table => $columns) {
                if (! $schema->hasTable($table)) {
                    $issues[] = "Tabel {$table} tidak ditemukan.";

                    continue;
                }

                foreach ($columns as $column) {
                    if (! $schema->hasColumn($table, $column)) {
                        $issues[] = "Kolom {$table}.{$column} tidak ditemukan.";
                    }
                }
            }

            foreach ($signature['indexes'] ?? [] as $table => $indexes) {
                foreach ($indexes as $index) {
                    if (! $this->indexExists($connection, $table, $index)) {
                        $issues[] = "Index {$table}.{$index} tidak ditemukan.";
                    }
                }
            }

            foreach ($signature['foreign_keys'] ?? [] as $table => $foreignKeys) {
                foreach ($foreignKeys as $foreignKey) {
                    if (! $this->foreignKeyExists($connection, $table, $foreignKey)) {
                        $issues[] = "Foreign key {$table}.{$foreignKey} tidak ditemukan.";
                    }
                }
            }

            foreach ($signature['enum_values'] ?? [] as $qualifiedColumn => $values) {
                [$table, $column] = explode('.', $qualifiedColumn, 2);
                $columnType = $this->columnType($connection, $table, $column);

                foreach ($values as $value) {
                    if ($columnType === null || ! str_contains($columnType, "'{$value}'")) {
                        $issues[] = "Nilai ENUM {$qualifiedColumn}={$value} tidak ditemukan.";
                    }
                }
            }

            $recorded = $connection->table('migrations')
                ->where('migration', $migration)
                ->exists();

            $results[$migration] = [
                'status' => $recorded ? 'recorded' : ($issues === [] ? 'ready' : 'blocked'),
                'issues' => $issues,
            ];
        }

        return $results;
    }

    /**
     * @param  array<string, array{status: string, issues: list<string>}>  $audit
     * @return array{batch: int|null, recorded: list<string>}
     */
    public function recordVerified(Connection $connection, array $audit): array
    {
        $blocked = array_filter($audit, fn (array $result): bool => $result['status'] === 'blocked');

        if ($blocked !== []) {
            throw new RuntimeException('Ledger tidak diubah karena masih ada signature migrasi yang gagal.');
        }

        $ready = array_keys(array_filter(
            $audit,
            fn (array $result): bool => $result['status'] === 'ready',
        ));

        if ($ready === []) {
            return ['batch' => null, 'recorded' => []];
        }

        return $connection->transaction(function () use ($connection, $ready): array {
            $alreadyRecorded = $connection->table('migrations')
                ->whereIn('migration', $ready)
                ->pluck('migration')
                ->all();
            $toRecord = array_values(array_diff($ready, $alreadyRecorded));

            if ($toRecord === []) {
                return ['batch' => null, 'recorded' => []];
            }

            $batch = ((int) $connection->table('migrations')->max('batch')) + 1;

            $connection->table('migrations')->insert(array_map(
                fn (string $migration): array => [
                    'migration' => $migration,
                    'batch' => $batch,
                ],
                $toRecord,
            ));

            return ['batch' => $batch, 'recorded' => $toRecord];
        });
    }

    private function assertSupportedConnection(Connection $connection): void
    {
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('Rekonsiliasi ledger hanya mendukung koneksi MySQL/MariaDB.');
        }

        if (! $connection->getSchemaBuilder()->hasTable('migrations')) {
            throw new RuntimeException('Tabel migrations tidak ditemukan.');
        }
    }

    private function indexExists(Connection $connection, string $table, string $index): bool
    {
        return (int) ($connection->selectOne(
            'SELECT COUNT(*) AS total FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$connection->getDatabaseName(), $table, $index],
        )->total ?? 0) > 0;
    }

    private function foreignKeyExists(Connection $connection, string $table, string $foreignKey): bool
    {
        return (int) ($connection->selectOne(
            "SELECT COUNT(*) AS total FROM information_schema.table_constraints
             WHERE table_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$connection->getDatabaseName(), $table, $foreignKey],
        )->total ?? 0) > 0;
    }

    private function columnType(Connection $connection, string $table, string $column): ?string
    {
        return $connection->selectOne(
            'SELECT column_type FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$connection->getDatabaseName(), $table, $column],
        )->column_type ?? null;
    }
}
