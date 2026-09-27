<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, array<int, string>>> */
    private array $indexes = [
        'sessions' => [
            'sessions_user_perf_idx' => ['user_id'],
            'sessions_activity_perf_idx' => ['last_activity'],
        ],
        'projects' => [
            'projects_program_updated_perf_idx' => ['program', 'updated_at'],
        ],
        'lops' => [
            'lops_branch_perf_idx' => ['branch'],
            'lops_status_progress_perf_idx' => ['status_progress'],
            'lops_active_state_perf_idx' => ['is_golive', 'sdi_approval_status', 'status_progress'],
        ],
        'evidences' => [
            'evidences_status_project_perf_idx' => ['status', 'project_id'],
            'evidences_project_updated_perf_idx' => ['project_id', 'updated_at'],
        ],
        'pro_assign' => [
            'pro_assign_project_latest_perf_idx' => ['project_id', 'id_proassign'],
            'pro_assign_admin_project_perf_idx' => ['assigned_by', 'project_id'],
            'pro_assign_waspang_perf_idx' => ['waspang_id'],
            'pro_assign_teknisi_perf_idx' => ['teknisi_id'],
        ],
        'lop_stage_histories' => [
            'lop_history_open_stage_perf_idx' => ['lop_id', 'completed_at', 'entered_at'],
        ],
        'project_activity_logs' => [
            'activity_lop_created_perf_idx' => ['lop_id', 'created_at'],
            'activity_project_created_perf_idx' => ['project_id', 'created_at'],
        ],
        'pt2_lops' => [
            'pt2_lops_branch_perf_idx' => ['branch'],
            'pt2_lops_status_perf_idx' => ['status_progress'],
            'pt2_lops_active_state_perf_idx' => ['is_golive', 'sdi_approval_status', 'status_progress'],
        ],
        'pt2_evidences' => [
            'pt2_evidence_status_lop_perf_idx' => ['status', 'pt2_lop_id'],
            'pt2_evidence_lop_updated_perf_idx' => ['pt2_lop_id', 'updated_at'],
        ],
        'pt2_assignments' => [
            'pt2_assign_lop_latest_perf_idx' => ['pt2_lop_id', 'id_pt2_assignment'],
            'pt2_assign_admin_lop_perf_idx' => ['assigned_by', 'pt2_lop_id'],
            'pt2_assign_teknisi_perf_idx' => ['teknisi_id'],
        ],
        'notifications' => [
            'notifications_user_created_perf_idx' => ['user_id', 'created_at'],
        ],
    ];

    public function up(): void
    {
        $this->addSessionsPrimaryKey();

        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                $this->addIndex($table, $columns, $name);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->indexes, true) as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_reverse($indexes, true) as $name => $columns) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }

        // Primary key sessions adalah kontrak schema dasar Laravel. Jangan
        // menghapusnya saat rollback optimasi, termasuk pada database lama
        // yang mungkin sudah memilikinya sebelum migration ini dijalankan.
    }

    /** @param array<int, string> $columns */
    private function addIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        // Cek berdasarkan susunan kolom, bukan hanya nama. Ini mencegah
        // index duplikat pada database lama yang memakai nama berbeda.
        if (Schema::hasIndex($table, $columns)) {
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
    }

    private function addSessionsPrimaryKey(): void
    {
        if (! Schema::hasTable('sessions')
            || ! Schema::hasColumn('sessions', 'id')
            || Schema::hasIndex('sessions', ['id'], 'primary')) {
            return;
        }

        $hasDuplicate = DB::table('sessions')
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $hasEmptyId = DB::table('sessions')->whereNull('id')->orWhere('id', '')->exists();

        if (! $hasDuplicate && ! $hasEmptyId) {
            Schema::table('sessions', fn (Blueprint $table) => $table->primary('id'));
        }
    }
};
