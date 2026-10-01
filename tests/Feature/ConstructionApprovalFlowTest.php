<?php

namespace Tests\Feature;

use App\Http\Controllers\ProjectController;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ConstructionApprovalFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Schema::dropAllTables();
        $this->createSchema();
        $this->seedApprovalFlow();
        $this->actingAs(User::findOrFail(1));
    }

    public function test_measurement_review_displays_canonical_and_legacy_evidence_types(): void
    {
        $this->withoutVite();
        $this->app['view']->share('errors', new ViewErrorBag);

        $view = app(ProjectController::class)->reviewPengukuran(10);
        $html = $view->render();

        $this->assertStringContainsString('measurement/file.sor', $html);
        $this->assertStringContainsString('measurement/other.jpg', $html);
        $this->assertStringContainsString('Step 4 — Pengukuran', $html);
    }

    public function test_bulk_approval_synchronizes_measurement_checks_and_advances_to_finishing(): void
    {
        app(ProjectController::class)->bulkApprove(Request::create('/', 'POST', [
            'evidence_ids' => [204, 205],
        ]));

        $this->assertDatabaseHas('evidences', ['id_evidence' => 204, 'status' => 'approved']);
        $this->assertDatabaseHas('evidences', ['id_evidence' => 205, 'status' => 'approved']);
        $this->assertDatabaseHas('lop_measurement_checks', [
            'lop_id' => 20,
            'item_key' => 'file_sor',
            'evidence_id' => 204,
        ]);
        $this->assertDatabaseHas('lop_measurement_checks', [
            'lop_id' => 20,
            'item_key' => 'eviden_lainnya',
            'evidence_id' => 205,
        ]);
        $this->assertDatabaseHas('lops', [
            'id_lop' => 20,
            'status_progress' => 'finishing',
        ]);

        $summary = Project::findOrFail(10)->progressSummary();
        $this->assertTrue($summary['pengukuranDone']);
        $this->assertTrue($summary['finishingDone']);
    }

    public function test_fi_ogp_submit_repairs_old_approved_measurements_before_gate_check(): void
    {
        DB::table('evidences')->whereIn('id_evidence', [204, 205])->update(['status' => 'approved']);
        DB::table('lop_golive_submissions')->insert([
            'lop_id' => 20,
            'capture_valins_paths' => json_encode(['valins.jpg']),
            'abd_valid4_paths' => json_encode(['abd.pdf']),
            'kml_paths' => json_encode(['route.kml']),
            'mancore_paths' => json_encode(['mancore.xlsx']),
            'submission_status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ProjectController::class)->submitGoliveDocuments(Request::create('/', 'POST'), 10);

        $this->assertDatabaseHas('lop_golive_submissions', [
            'lop_id' => 20,
            'submission_status' => 'submitted',
            'submitted_by' => 1,
        ]);
        $this->assertDatabaseHas('lops', [
            'id_lop' => 20,
            'status_progress' => 'fi_ogp_golive',
        ]);
        $this->assertDatabaseHas('telegram_webhook_events', [
            'event_type' => 'sdi_verification_requested',
            'recipient_type' => 'role',
            'recipient_role' => 'sdi',
            'project_id' => 10,
            'lop_id' => 20,
        ]);
    }

    public function test_finishing_is_complete_when_no_designator_requires_final_evidence(): void
    {
        DB::table('designators')->where('id_designator', 30)->update(['requires_finishing_evidence' => 0]);
        DB::table('evidences')->where('id_evidence', 300)->delete();
        DB::table('lops')->where('id_lop', 20)->update(['status_progress' => 'finishing']);

        $project = Project::findOrFail(10);

        $this->assertTrue($project->stepUploadFlags()['finishingUploaded']);
        $this->assertTrue($project->progressSummary()['finishingDone']);
    }

    public function test_instalasi_and_finishing_use_latest_completed_survey_volume_with_plan_fallback(): void
    {
        DB::table('boq_survey_rounds')->insert([
            ['id' => 40, 'lop_id' => 20, 'round_number' => 1, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 41, 'lop_id' => 20, 'round_number' => 2, 'status' => 'completed', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('boq_survey_round_items')->insert([
            [
                'boq_survey_round_id' => 40,
                'boq_item_id' => 100,
                'designator_id' => 30,
                'designator' => 'M-TEST',
                'item_name' => 'Material Test',
                'unit' => 'UNIT',
                'quantity_plan' => 1,
                'quantity_survey' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'boq_survey_round_id' => 41,
                'boq_item_id' => 100,
                'designator_id' => 30,
                'designator' => 'M-TEST',
                'item_name' => 'Material Test',
                'unit' => 'UNIT',
                'quantity_plan' => 1,
                'quantity_survey' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $instalasiView = app(ProjectController::class)->reviewInstalasi(10);
        $instalasiSource = $instalasiView->getData()['materialSource'];
        $instalasiItems = $instalasiView->getData()['materialBoqItems'];

        $this->assertSame('survey_round', $instalasiSource['source']);
        $this->assertSame(2, $instalasiSource['round']->round_number);
        $this->assertSame(9.0, $instalasiItems->first()->quantity_plan);

        $finishingView = app(ProjectController::class)->reviewFinishing(10);
        $this->assertSame(9.0, $finishingView->getData()['materialBoqItems']->first()->quantity_plan);

        DB::table('boq_survey_round_items')->delete();
        DB::table('boq_survey_rounds')->delete();
        $fallback = Project::findOrFail(10)->materialProgressItems();

        $this->assertSame('plan', $fallback['source']);
        $this->assertSame(1.0, $fallback['items']->first()->quantity_plan);
    }

    public function test_approval_tabs_follow_actual_admin_approval_and_golive_completion(): void
    {
        foreach ([11, 12, 13, 14, 15] as $projectId) {
            DB::table('projects')->insert([
                'id_project' => $projectId,
                'project_name' => "Approval Project {$projectId}",
                'program' => 'OSP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('lops')->insert([
            ['id_lop' => 21, 'project_id' => 11, 'lop_name' => 'Pending', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'survey', 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 22, 'project_id' => 12, 'lop_name' => 'Active', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'instalasi', 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 23, 'project_id' => 13, 'lop_name' => 'Complete', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'golive', 'is_golive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 24, 'project_id' => 14, 'lop_name' => 'Waiting SDI', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'fi_ogp_golive', 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 25, 'project_id' => 15, 'lop_name' => 'Mixed Golive', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'golive', 'is_golive' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 26, 'project_id' => 15, 'lop_name' => 'Mixed Active', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'finishing', 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $evidences = [
            [401, 11, 'pending'],
            [402, 12, 'approved'],
            [403, 12, 'pending'],
            [404, 13, 'approved'],
            [405, 14, 'approved'],
            [406, 15, 'approved'],
        ];
        foreach ($evidences as [$id, $projectId, $status]) {
            DB::table('evidences')->insert([
                'id_evidence' => $id,
                'project_id' => $projectId,
                'uploaded_by' => 1,
                'stage' => 'instalasi',
                'evidence_type' => 'progress_boq',
                'file_path' => "approval/{$id}.jpg",
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $pending = app(ProjectController::class)
            ->approvalIndex(Request::create('/', 'GET', ['status_filter' => 'pending']))
            ->getData()['projects']->pluck('id_project')->all();
        $active = app(ProjectController::class)
            ->approvalIndex(Request::create('/', 'GET', ['status_filter' => 'active']))
            ->getData()['projects']->pluck('id_project')->sort()->values()->all();
        $complete = app(ProjectController::class)
            ->approvalIndex(Request::create('/', 'GET', ['status_filter' => 'complete']))
            ->getData()['projects']->pluck('id_project')->all();

        $this->assertSame([11], $pending);
        $this->assertSame([12, 14, 15], $active);
        $this->assertSame([13], $complete);

        $searchWithinPending = app(ProjectController::class)
            ->approvalIndex(Request::create('/', 'GET', [
                'search' => 'Approval Project 13',
                'status_filter' => 'pending',
            ]))
            ->getData()['projects']->pluck('id_project')->all();

        $this->assertSame([], $searchWithinPending);
    }

    private function seedApprovalFlow(): void
    {
        DB::table('users')->insert([
            'id_user' => 1,
            'name' => 'Admin Test',
            'username' => 'admin-test',
            'password' => bcrypt('secret'),
            'role' => 'admin',
        ]);
        DB::table('projects')->insert([
            'id_project' => 10,
            'project_name' => 'Project Approval Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('project_stages')->insert([
            ['code' => 'pengukuran', 'label' => 'Pengukuran', 'sequence' => 8, 'is_pause_type' => 0, 'is_terminal' => 0],
            ['code' => 'finishing', 'label' => 'Finishing', 'sequence' => 9, 'is_pause_type' => 0, 'is_terminal' => 0],
            ['code' => 'fi_ogp_golive', 'label' => 'FI-OGP Golive', 'sequence' => 10, 'is_pause_type' => 0, 'is_terminal' => 0],
        ]);
        DB::table('lops')->insert([
            'id_lop' => 20,
            'project_id' => 10,
            'lop_name' => 'LOP Approval',
            'status_progress' => 'pengukuran',
            'program_sap' => 'OSP-FTTH',
            'is_golive' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('designators')->insert([
            'id_designator' => 30,
            'designator' => 'M-TEST',
            'item_name' => 'Material Test',
            'unit' => 'UNIT',
            'type' => 'material',
            'requires_finishing_evidence' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('boq_items')->insert([
            'id_boq' => 100,
            'project_id' => 10,
            'lop_id' => 20,
            'designator_id' => 30,
            'designator' => 'M-TEST',
            'item_name' => 'Material Test',
            'unit' => 'UNIT',
            'quantity_plan' => 1,
            'quantity_actual' => 1,
        ]);

        $evidences = [
            [100, 'instalasi', 'progress_boq', 'approved', 100, 'installation.jpg'],
            [201, 'pengukuran', 'otdr', 'approved', null, 'measurement/otdr.jpg'],
            [202, 'pengukuran', 'opm', 'approved', null, 'measurement/opm.jpg'],
            [203, 'pengukuran', 'kedalaman', 'approved', null, 'measurement/depth.jpg'],
            [204, 'pengukuran', 'file_sor', 'pending', null, 'measurement/file.sor'],
            [205, 'pengukuran', 'eviden_lainnya', 'pending', null, 'measurement/other.jpg'],
            [300, 'finishing', 'final_boq', 'approved', 100, 'finishing/final.jpg'],
        ];

        foreach ($evidences as [$id, $stage, $type, $status, $boqItemId, $path]) {
            DB::table('evidences')->insert([
                'id_evidence' => $id,
                'project_id' => 10,
                'boq_item_id' => $boqItemId,
                'uploaded_by' => 1,
                'stage' => $stage,
                'evidence_type' => $type,
                'file_path' => $path,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id('id_user');
            $table->string('name');
            $table->string('username');
            $table->string('password');
            $table->string('role')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id('id_project');
            $table->string('project_name');
            $table->string('program')->nullable();
            $table->timestamps();
        });
        Schema::create('pro_assign', function (Blueprint $table): void {
            $table->id('id_proassign');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('waspang_id')->nullable();
            $table->timestamps();
        });
        Schema::create('project_stages', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->unsignedInteger('sequence')->nullable();
            $table->string('phase_group')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_pause_type')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
        });
        Schema::create('lops', function (Blueprint $table): void {
            $table->id('id_lop');
            $table->unsignedBigInteger('project_id');
            $table->string('lop_name');
            $table->string('branch')->nullable();
            $table->string('sto')->nullable();
            $table->string('status_progress');
            $table->string('status_progress_before_hold')->nullable();
            $table->string('program_sap')->nullable();
            $table->boolean('is_golive')->default(false);
            $table->timestamps();
        });
        Schema::create('designators', function (Blueprint $table): void {
            $table->id('id_designator');
            $table->string('designator');
            $table->string('item_name');
            $table->string('unit');
            $table->string('type')->nullable();
            $table->string('pair_code')->nullable();
            $table->boolean('requires_finishing_evidence')->default(false);
            $table->timestamps();
        });
        Schema::create('boq_items', function (Blueprint $table): void {
            $table->id('id_boq');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('lop_id');
            $table->unsignedBigInteger('designator_id')->nullable();
            $table->string('designator')->nullable();
            $table->string('item_name');
            $table->string('unit')->nullable();
            $table->float('quantity_plan')->nullable();
            $table->float('quantity_survey')->nullable();
            $table->float('quantity_actual')->nullable();
        });
        Schema::create('boq_survey_rounds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->unsignedInteger('round_number');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('boq_survey_round_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('boq_survey_round_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->unsignedBigInteger('designator_id')->nullable();
            $table->string('designator')->nullable();
            $table->string('item_name')->nullable();
            $table->string('unit')->nullable();
            $table->float('quantity_plan')->nullable();
            $table->float('quantity_survey')->nullable();
            $table->timestamps();
        });
        Schema::create('evidences', function (Blueprint $table): void {
            $table->id('id_evidence');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('stage');
            $table->string('evidence_type');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->text('review_note')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_measurement_checks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('item_key');
            $table->boolean('is_not_applicable')->default(false);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamps();
            $table->unique(['lop_id', 'item_key']);
        });
        Schema::create('lop_stage_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('stage_code');
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('project_activity_logs', function (Blueprint $table): void {
            $table->id('id_project_activity');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->string('activity_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('stage')->nullable();
            $table->string('status_before')->nullable();
            $table->string('status_after')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id('id_notification');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('redirect_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_golive_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lop_id')->unique();
            $table->string('capture_valins_path')->nullable();
            $table->json('capture_valins_paths')->nullable();
            $table->string('abd_valid4_path')->nullable();
            $table->json('abd_valid4_paths')->nullable();
            $table->string('kml_path')->nullable();
            $table->json('kml_paths')->nullable();
            $table->string('mancore_path')->nullable();
            $table->json('mancore_paths')->nullable();
            $table->string('mancore_input_type')->nullable();
            $table->timestamp('fi_completed_at')->nullable();
            $table->string('submission_status')->default('draft');
            $table->unsignedBigInteger('draft_saved_by')->nullable();
            $table->timestamp('draft_saved_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
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
