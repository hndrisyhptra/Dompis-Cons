<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertEveryProjectHasALop('projects', 'lops', 'id_project', 'project_id');
        $this->assertEveryProjectHasALop('pt2_projects', 'pt2_lops', 'id_pt2_project', 'pt2_project_id');

        // Rekonsiliasi terakhir sebelum sumber status pada tabel induk dilepas.
        if ($this->hasColumns('projects', ['sdi_approval_status', 'is_golive', 'golive_evidence_path', 'golive_at'])) {
            DB::statement(<<<'SQL'
                UPDATE lops l
                INNER JOIN projects p ON p.id_project = l.project_id
                SET l.sdi_approval_status = CASE
                        WHEN p.sdi_approval_status = 'approved' THEN 'approved'
                        ELSE COALESCE(l.sdi_approval_status, p.sdi_approval_status, 'pending')
                    END,
                    l.is_golive = CASE WHEN p.is_golive = 1 THEN 1 ELSE COALESCE(l.is_golive, 0) END,
                    l.golive_evidence_path = COALESCE(l.golive_evidence_path, p.golive_evidence_path),
                    l.golive_at = COALESCE(l.golive_at, p.golive_at),
                    l.status_progress = CASE
                        WHEN p.is_golive = 1 OR p.sdi_approval_status = 'approved' THEN 'golive'
                        WHEN p.status_project = 'drop' THEN 'drop'
                        ELSE l.status_progress
                    END
            SQL);
        }

        if ($this->hasColumns('pt2_projects', ['status_project', 'sdi_approval_status', 'is_golive'])) {
            DB::statement(<<<'SQL'
                UPDATE pt2_lops l
                INNER JOIN pt2_projects p ON p.id_pt2_project = l.pt2_project_id
                SET l.sdi_approval_status = CASE
                        WHEN p.sdi_approval_status = 'approved' THEN 'approved'
                        ELSE l.sdi_approval_status
                    END,
                    l.is_golive = CASE WHEN p.is_golive = 1 THEN 1 ELSE COALESCE(l.is_golive, 0) END,
                    l.status_progress = CASE
                        WHEN p.is_golive = 1 OR p.sdi_approval_status = 'approved' THEN 'golive'
                        WHEN p.status_project = 'drop' THEN 'drop'
                        ELSE l.status_progress
                    END
            SQL);
        }

        $this->dropColumnsIfPresent('projects', [
            'status',
            'status_project',
            'sdi_approval_status',
            'is_golive',
            'golive_evidence_path',
            'golive_at',
        ]);

        $this->dropColumnsIfPresent('pt2_projects', [
            'status',
            'status_project',
            'sdi_approval_status',
            'is_golive',
        ]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('status', ['active', 'completed', 'waiting_ut'])->default('active')->after('jenis_eksekusi');
            $table->enum('status_project', ['init', 'active', 'close', 'bast', 'drop'])->default('init')->after('execution_type');
            $table->enum('sdi_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('status_project');
            $table->boolean('is_golive')->default(false)->after('sdi_approval_status');
            $table->string('golive_evidence_path')->nullable()->after('is_golive');
            $table->timestamp('golive_at')->nullable()->after('golive_evidence_path');
        });

        DB::statement(<<<'SQL'
            UPDATE projects p
            LEFT JOIN (
                SELECT project_id,
                       MAX(status_progress = 'drop') any_drop,
                       MAX(status_progress = 'golive' OR is_golive = 1) any_golive,
                       MAX(status_progress = 'fi_ogp_golive') any_waiting,
                       MAX(sdi_approval_status = 'approved') any_approved,
                       MAX(sdi_approval_status = 'rejected') any_rejected,
                       MAX(is_golive) is_golive,
                       MAX(golive_at) golive_at
                FROM lops
                GROUP BY project_id
            ) x ON x.project_id = p.id_project
            SET p.status_project = CASE
                    WHEN x.any_drop = 1 THEN 'drop'
                    WHEN x.any_golive = 1 THEN 'close'
                    WHEN x.any_waiting = 1 THEN 'bast'
                    ELSE 'active'
                END,
                p.status = CASE
                    WHEN x.any_golive = 1 THEN 'completed'
                    WHEN x.any_waiting = 1 THEN 'waiting_ut'
                    ELSE 'active'
                END,
                p.sdi_approval_status = CASE
                    WHEN x.any_approved = 1 THEN 'approved'
                    WHEN x.any_rejected = 1 THEN 'rejected'
                    ELSE 'pending'
                END,
                p.is_golive = COALESCE(x.is_golive, 0),
                p.golive_at = x.golive_at,
                p.golive_evidence_path = (
                    SELECT l.golive_evidence_path
                    FROM lops l
                    WHERE l.project_id = p.id_project
                      AND l.golive_evidence_path IS NOT NULL
                    ORDER BY l.golive_at DESC, l.id_lop DESC
                    LIMIT 1
                )
        SQL);

        Schema::table('pt2_projects', function (Blueprint $table) {
            $table->string('status_project', 50)->default('active')->after('execution_type');
            $table->string('status', 50)->nullable()->after('status_project');
            $table->boolean('is_golive')->default(false)->after('status');
            $table->string('sdi_approval_status', 50)->nullable()->after('is_golive');
        });

        DB::statement(<<<'SQL'
            UPDATE pt2_projects p
            LEFT JOIN (
                SELECT pt2_project_id,
                       MAX(status_progress = 'drop') any_drop,
                       MAX(status_progress IN ('complete', 'mancore', 'golive') OR is_golive = 1) any_complete,
                       MAX(sdi_approval_status = 'approved') any_approved,
                       MAX(sdi_approval_status = 'pending') any_pending,
                       MAX(is_golive) is_golive
                FROM pt2_lops
                GROUP BY pt2_project_id
            ) x ON x.pt2_project_id = p.id_pt2_project
            SET p.status_project = CASE
                    WHEN x.any_drop = 1 THEN 'drop'
                    WHEN x.any_complete = 1 THEN 'bast'
                    ELSE 'active'
                END,
                p.status = CASE WHEN x.any_complete = 1 THEN 'completed' ELSE 'active' END,
                p.is_golive = COALESCE(x.is_golive, 0),
                p.sdi_approval_status = CASE
                    WHEN x.any_approved = 1 THEN 'approved'
                    WHEN x.any_pending = 1 THEN 'pending'
                    ELSE NULL
                END
        SQL);
    }

    private function assertEveryProjectHasALop(
        string $projectTable,
        string $lopTable,
        string $projectKey,
        string $lopForeignKey,
    ): void {
        if (! Schema::hasTable($projectTable) || ! Schema::hasTable($lopTable)) {
            return;
        }

        $orphanCount = DB::table("{$projectTable} as p")
            ->whereNotExists(function ($query) use ($lopTable, $projectKey, $lopForeignKey) {
                $query->selectRaw('1')
                    ->from("{$lopTable} as l")
                    ->whereColumn("l.{$lopForeignKey}", "p.{$projectKey}");
            })
            ->count();

        if ($orphanCount > 0) {
            throw new \RuntimeException(
                "Penghapusan status induk dibatalkan: {$orphanCount} baris {$projectTable} belum memiliki LOP."
            );
        }
    }

    private function dropColumnsIfPresent(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($table, $column),
        ));

        if ($existing === []) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($existing));
    }

    private function hasColumns(string $table, array $columns): bool
    {
        return Schema::hasTable($table)
            && collect($columns)->every(fn (string $column) => Schema::hasColumn($table, $column));
    }
};
