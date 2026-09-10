<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lops', function (Blueprint $table) {
            if (! Schema::hasColumn('lops', 'sdi_approval_status')) {
                $table->string('sdi_approval_status', 30)->default('pending')->after('status_progress_before_hold');
            }

            if (! Schema::hasColumn('lops', 'is_golive')) {
                $table->boolean('is_golive')->default(false)->after('sdi_approval_status');
            }

            if (! Schema::hasColumn('lops', 'golive_evidence_path')) {
                $table->string('golive_evidence_path')->nullable()->after('is_golive');
            }

            if (! Schema::hasColumn('lops', 'golive_at')) {
                $table->timestamp('golive_at')->nullable()->after('golive_evidence_path');
            }
        });

        // Salin status persetujuan dan Golive project lama ke setiap LOP.
        DB::statement(<<<'SQL'
            UPDATE lops l
            INNER JOIN projects p ON p.id_project = l.project_id
            SET l.sdi_approval_status = COALESCE(p.sdi_approval_status, 'pending'),
                l.is_golive = COALESCE(p.is_golive, 0),
                l.golive_evidence_path = p.golive_evidence_path,
                l.golive_at = p.golive_at
        SQL);

        // Flag Golive/approval lama lebih kuat daripada label progres lama.
        DB::statement(<<<'SQL'
            UPDATE lops l
            INNER JOIN projects p ON p.id_project = l.project_id
            SET l.status_progress = 'golive',
                l.sdi_approval_status = 'approved',
                l.is_golive = 1
            WHERE p.is_golive = 1 OR p.sdi_approval_status = 'approved'
        SQL);

        // status_project=drop dipindahkan ke status_progress per LOP dan posisi
        // sebelumnya disimpan agar fitur resume tetap bekerja.
        DB::statement(<<<'SQL'
            UPDATE lops l
            INNER JOIN projects p ON p.id_project = l.project_id
            SET l.status_progress_before_hold = CASE
                    WHEN l.status_progress NOT IN ('hold', 'drop') THEN l.status_progress
                    ELSE l.status_progress_before_hold
                END,
                l.status_progress = 'drop'
            WHERE p.status_project = 'drop'
        SQL);

        // DRM tidak lagi menjadi tahap aktif.
        DB::table('lops')->where('status_progress', 'drm')->update(['status_progress' => 'perizinan']);
        DB::table('lops')->where('status_progress_before_hold', 'drm')->update(['status_progress_before_hold' => 'perizinan']);

        Schema::table('lops', function (Blueprint $table) {
            $table->index('sdi_approval_status', 'lops_sdi_approval_status_idx');
            $table->index('is_golive', 'lops_is_golive_idx');
        });
    }

    public function down(): void
    {
        // Kolom project lama tetap tersedia pada tahap konsolidasi ini, jadi
        // rollback cukup menghapus salinan yang baru ditambahkan ke LOP.
        Schema::table('lops', function (Blueprint $table) {
            $table->dropIndex('lops_sdi_approval_status_idx');
            $table->dropIndex('lops_is_golive_idx');
            $table->dropColumn([
                'sdi_approval_status',
                'is_golive',
                'golive_evidence_path',
                'golive_at',
            ]);
        });
    }
};
