<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LopGoliveSubmissionMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_user')->primary();
        });
        Schema::create('lops', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_lop')->primary();
            $table->string('status_progress')->nullable();
        });
        Schema::create('lop_golive_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id')->unique();
            $table->timestamp('fi_completed_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_golive_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id')->unique();
        });
    }

    public function test_existing_submission_state_is_backfilled_without_locking_drafts(): void
    {
        DB::table('users')->insert([['id_user' => 1], ['id_user' => 2]]);
        DB::table('lops')->insert([
            ['id_lop' => 10, 'status_progress' => 'fi_ogp_golive'],
            ['id_lop' => 20, 'status_progress' => 'finishing'],
        ]);
        DB::table('lop_golive_submissions')->insert([
            [
                'id' => 100,
                'lop_id' => 10,
                'submitted_by' => 1,
                'submitted_at' => '2026-09-20 09:00:00',
                'created_at' => '2026-09-20 09:00:00',
                'updated_at' => '2026-09-20 09:00:00',
            ],
            [
                'id' => 200,
                'lop_id' => 20,
                'submitted_by' => 2,
                'submitted_at' => '2026-09-21 10:00:00',
                'created_at' => '2026-09-21 10:00:00',
                'updated_at' => '2026-09-21 10:00:00',
            ],
        ]);

        $migration = require database_path('migrations/2026_09_22_090000_add_draft_state_to_lop_golive_submissions_table.php');
        $migration->up();

        $this->assertDatabaseHas('lop_golive_submissions', [
            'id' => 100,
            'submission_status' => 'submitted',
            'draft_saved_by' => 1,
            'submitted_by' => 1,
        ]);
        $this->assertDatabaseHas('lop_golive_submissions', [
            'id' => 200,
            'submission_status' => 'draft',
            'draft_saved_by' => 2,
            'submitted_by' => null,
            'submitted_at' => null,
        ]);
    }
}
