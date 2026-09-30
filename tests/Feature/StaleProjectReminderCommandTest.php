<?php

namespace Tests\Feature;

use App\Jobs\PushTelegramWebhookEventJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaleProjectReminderCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-30 08:00:00');
        Queue::fake();
        Schema::dropAllTables();
        $this->createSchema();
        $this->seedOverdueLop();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_overdue_pt3_lop_notifies_assigner_waspang_and_pm_once_per_day(): void
    {
        $this->artisan('webhook:publish-stale-project-reminders')->assertSuccessful();

        $this->assertDatabaseHas('telegram_webhook_events', [
            'event_type' => 'project_stale_reminder',
            'recipient_type' => 'user',
            'recipient_user_id' => 1,
            'project_id' => 10,
            'lop_id' => 20,
        ]);
        $this->assertDatabaseHas('telegram_webhook_events', [
            'event_type' => 'project_stale_reminder',
            'recipient_type' => 'user',
            'recipient_user_id' => 2,
            'project_id' => 10,
            'lop_id' => 20,
        ]);
        $this->assertDatabaseHas('telegram_webhook_events', [
            'event_type' => 'project_stale_reminder',
            'recipient_type' => 'role',
            'recipient_role' => 'pm',
            'project_id' => 10,
            'lop_id' => 20,
        ]);
        $this->assertSame(3, DB::table('telegram_webhook_events')->count());
        Queue::assertPushed(PushTelegramWebhookEventJob::class, 3);

        $this->artisan('webhook:publish-stale-project-reminders')->assertSuccessful();
        $this->assertSame(3, DB::table('telegram_webhook_events')->count());
    }

    private function seedOverdueLop(): void
    {
        DB::table('users')->insert([
            ['id_user' => 1, 'name' => 'Admin Area', 'username' => 'admin', 'role' => 'admin'],
            ['id_user' => 2, 'name' => 'Waspang Area', 'username' => 'waspang', 'role' => 'waspang'],
        ]);
        DB::table('projects')->insert([
            'id_project' => 10,
            'pid' => 'PID-OVERDUE',
            'project_name' => 'Project Overdue',
        ]);
        DB::table('permit_categories')->insert([
            'id' => 30,
            'name' => 'PERIZINAN PU NASIONAL',
        ]);
        DB::table('lops')->insert([
            'id_lop' => 20,
            'project_id' => 10,
            'lop_name' => 'LOP Overdue',
            'branch' => 'MATARAM',
            'sto' => 'MTR',
            'start_tgl' => '2026-01-01',
            'permit_category_id' => 30,
            'status_progress' => 'survey',
            'is_golive' => 0,
        ]);
        DB::table('pro_assign')->insert([
            'project_id' => 10,
            'waspang_id' => 2,
            'assigned_by' => 1,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id('id_roles');
            $table->string('code');
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id('id_user');
            $table->string('name');
            $table->string('username');
            $table->string('role')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id('id_project');
            $table->string('pid')->nullable();
            $table->string('project_name');
        });
        Schema::create('permit_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        Schema::create('lops', function (Blueprint $table): void {
            $table->id('id_lop');
            $table->unsignedBigInteger('project_id');
            $table->string('lop_name');
            $table->string('branch')->nullable();
            $table->string('sto')->nullable();
            $table->date('start_tgl')->nullable();
            $table->unsignedBigInteger('permit_category_id')->nullable();
            $table->string('status_progress');
            $table->timestamp('perizinan_completed_at')->nullable();
            $table->boolean('is_golive')->default(false);
            $table->timestamp('golive_at')->nullable();
        });
        Schema::create('pro_assign', function (Blueprint $table): void {
            $table->id('id_proassign');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('waspang_id')->nullable();
            $table->unsignedBigInteger('teknisi_id')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });
        Schema::create('boq_items', function (Blueprint $table): void {
            $table->id('id_boq');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->unsignedBigInteger('designator_id')->nullable();
            $table->string('designator')->nullable();
            $table->string('item_name')->nullable();
            $table->string('unit')->nullable();
            $table->float('quantity_plan')->nullable();
            $table->float('quantity_survey')->nullable();
        });
        Schema::create('evidences', function (Blueprint $table): void {
            $table->id('id_evidence');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->string('stage');
            $table->string('evidence_type');
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('boq_survey_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->unsignedInteger('round_number');
            $table->string('status');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_stage_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('stage_code');
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_measurement_checks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('item_key');
            $table->boolean('is_not_applicable')->default(false);
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_golive_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->timestamp('fi_completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('project_activity_logs', function (Blueprint $table): void {
            $table->id('id_project_activity');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->string('activity_type')->nullable();
            $table->string('stage')->nullable();
            $table->string('status_after')->nullable();
            $table->timestamps();
        });
        Schema::create('telegram_webhook_events', function (Blueprint $table): void {
            $table->id('id_tele_webhook');
            $table->string('event_type');
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_user_id')->nullable();
            $table->string('recipient_role')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }
}
