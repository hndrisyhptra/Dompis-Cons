<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lop_golive_submissions', function (Blueprint $table) {
            $table->string('submission_status', 20)->default('draft')->after('fi_completed_at')->index();
            $table->unsignedBigInteger('draft_saved_by')->nullable()->after('submission_status');
            $table->timestamp('draft_saved_at')->nullable()->after('draft_saved_by');

            $table->foreign('draft_saved_by')->references('id_user')->on('users')->nullOnDelete();
        });

        DB::table('lop_golive_submissions as submission')
            ->leftJoin('lops as lop', 'lop.id_lop', '=', 'submission.lop_id')
            ->leftJoin('lop_golive_verifications as verification', 'verification.lop_id', '=', 'submission.lop_id')
            ->select([
                'submission.id as submission_id',
                'submission.submitted_by',
                'submission.submitted_at',
                'submission.updated_at',
                'lop.status_progress',
                'verification.id as verification_id',
            ])
            ->orderBy('submission.id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $isSubmitted = in_array($row->status_progress, ['fi_ogp_golive', 'golive'], true)
                        || $row->verification_id !== null;

                    DB::table('lop_golive_submissions')
                        ->where('id', $row->submission_id)
                        ->update([
                            'submission_status' => $isSubmitted ? 'submitted' : 'draft',
                            'draft_saved_by' => $row->submitted_by,
                            'draft_saved_at' => $row->submitted_at ?: $row->updated_at,
                            'submitted_by' => $isSubmitted ? $row->submitted_by : null,
                            'submitted_at' => $isSubmitted ? $row->submitted_at : null,
                        ]);
                }
            }, 'submission.id', 'submission_id');
    }

    public function down(): void
    {
        Schema::table('lop_golive_submissions', function (Blueprint $table) {
            $table->dropForeign(['draft_saved_by']);
            $table->dropIndex(['submission_status']);
            $table->dropColumn(['submission_status', 'draft_saved_by', 'draft_saved_at']);
        });
    }
};
