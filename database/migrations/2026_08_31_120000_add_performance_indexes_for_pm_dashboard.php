<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan index pada kolom-kolom yang paling sering dipakai untuk JOIN/WHERE
 * di query Dashboard PM (dan Admin) — matrix Region/Branch/Program, Rekap Progress,
 * dan perhitungan Total Nilai (harga designator per package). Tabel-tabel inti
 * (lops, projects, boq_items, designators, designator_package_prices, dst) tidak
 * punya migration pembuatannya sendiri di project ini (kemungkinan hasil import SQL
 * manual), jadi index di kolom-kolom JOIN/WHERE ini kemungkinan besar belum ada.
 *
 * Aman dijalankan berkali-kali: setiap index dicek dulu keberadaannya lewat
 * information_schema sebelum dibuat, dan hanya dibuat kalau tabel & kolomnya ada.
 * Migration ini TIDAK mengubah data sama sekali, hanya menambah/menghapus index.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('lops', 'project_id', 'lops_project_id_perf_idx');
        $this->addIndex('lops', 'branch', 'lops_branch_perf_idx');
        $this->addIndex('lops', 'status_progress', 'lops_status_progress_perf_idx');
        $this->addIndex('lops', 'package_id', 'lops_package_id_perf_idx');

        $this->addIndex('projects', 'program', 'projects_program_perf_idx');
        $this->addIndex('projects', 'status_project', 'projects_status_project_perf_idx');

        $this->addIndex('boq_items', 'lop_id', 'boq_items_lop_id_perf_idx');
        $this->addIndex('boq_items', 'designator_id', 'boq_items_designator_id_perf_idx');

        $this->addIndex('designators', 'pair_code', 'designators_pair_code_perf_idx');
        $this->addIndex('designators', 'progress_category', 'designators_progress_category_perf_idx');

        $this->addCompositeIndex('designator_package_prices', ['designator_id', 'package_id'], 'dpp_designator_package_perf_idx');

        $this->addIndex('evidences', 'project_id', 'evidences_project_id_perf_idx');
        $this->addIndex('evidences', 'status', 'evidences_status_perf_idx');

        $this->addIndex('pro_assign', 'project_id', 'pro_assign_project_id_perf_idx');
        $this->addIndex('pro_assign', 'waspang_id', 'pro_assign_waspang_id_perf_idx');

        $this->addIndex('pt2_lops', 'pt2_project_id', 'pt2_lops_project_id_perf_idx');
    }

    public function down(): void
    {
        $this->dropIndexSafe('lops', 'lops_project_id_perf_idx');
        $this->dropIndexSafe('lops', 'lops_branch_perf_idx');
        $this->dropIndexSafe('lops', 'lops_status_progress_perf_idx');
        $this->dropIndexSafe('lops', 'lops_package_id_perf_idx');

        $this->dropIndexSafe('projects', 'projects_program_perf_idx');
        $this->dropIndexSafe('projects', 'projects_status_project_perf_idx');

        $this->dropIndexSafe('boq_items', 'boq_items_lop_id_perf_idx');
        $this->dropIndexSafe('boq_items', 'boq_items_designator_id_perf_idx');

        $this->dropIndexSafe('designators', 'designators_pair_code_perf_idx');
        $this->dropIndexSafe('designators', 'designators_progress_category_perf_idx');

        $this->dropIndexSafe('designator_package_prices', 'dpp_designator_package_perf_idx');

        $this->dropIndexSafe('evidences', 'evidences_project_id_perf_idx');
        $this->dropIndexSafe('evidences', 'evidences_status_perf_idx');

        $this->dropIndexSafe('pro_assign', 'pro_assign_project_id_perf_idx');
        $this->dropIndexSafe('pro_assign', 'pro_assign_waspang_id_perf_idx');

        $this->dropIndexSafe('pt2_lops', 'pt2_lops_project_id_perf_idx');
    }

    private function addIndex(string $table, string $column, string $indexName): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
            $t->index($column, $indexName);
        });
    }

    private function addCompositeIndex(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
            $t->index($columns, $indexName);
        });
    }

    private function dropIndexSafe(string $table, string $indexName): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($indexName) {
            $t->dropIndex($indexName);
        });
    }

    /**
     * Cek keberadaan index lewat information_schema langsung (bukan
     * Schema::hasIndex()) supaya migration ini tidak bergantung pada
     * doctrine/dbal atau versi Laravel tertentu.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$dbName, $table, $indexName]
        );

        return (int) ($result[0]->cnt ?? 0) > 0;
    }
};
