<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WaspangSurveyWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createWorkflowSchema();
        Storage::fake('public');
        $this->seedBaseWorkflow();
    }

    public function test_confirmed_map_allows_pair_volume_to_be_saved_as_one_row(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.map.confirm', 10))
            ->assertRedirect();

        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.boq.draft', 10), [
                'volumes' => ['100' => 7],
            ])
            ->assertRedirect();

        $this->assertSame([7, 7], DB::table('boq_items')->orderBy('id_boq')->pluck('quantity_actual')->all());
        $this->assertSame([8, 8], DB::table('boq_items')->orderBy('id_boq')->pluck('quantity_plan')->all());
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => 10,
            'lop_id' => 20,
            'activity_type' => 'survey_boq_draft_saved',
        ]);
    }

    public function test_redesign_uses_the_assigned_lop_name_automatically(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.redesign', 10))
            ->assertRedirect(route('surveyor.show', 1));

        $this->assertDatabaseHas('site_surveys', [
            'id_site_surveys' => 1,
            'project_id' => 10,
            'project_name' => 'LOP A',
            'title' => 'LOP A',
            'surveyor_id' => 1,
            'status' => 'draft',
        ]);
    }

    public function test_additional_designator_creates_material_and_service_pair_with_empty_plan(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.map.confirm', 10))
            ->assertRedirect();

        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.boq.additional.store', 10), [
                'designator_id' => 32,
                'volume_survey' => 4,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('boq_items', [
            'project_id' => 10,
            'lop_id' => 20,
            'designator_id' => 32,
            'quantity_plan' => null,
            'quantity_actual' => 4,
        ]);
        $this->assertDatabaseHas('boq_items', [
            'project_id' => 10,
            'lop_id' => 20,
            'designator_id' => 33,
            'quantity_plan' => null,
            'quantity_actual' => 4,
        ]);
    }

    public function test_finish_survey_saves_all_pair_volumes_and_moves_lop_to_perizinan(): void
    {
        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.map.confirm', 10))
            ->assertRedirect();

        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.finish', 10), [
                'volumes' => ['100' => 9],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lops', [
            'id_lop' => 20,
            'status_progress' => 'perizinan',
        ]);
        $this->assertSame([9, 9], DB::table('boq_items')->orderBy('id_boq')->pluck('quantity_actual')->all());
        $this->assertDatabaseHas('project_activity_logs', [
            'project_id' => 10,
            'lop_id' => 20,
            'activity_type' => 'survey_finalized',
            'status_before' => 'survey',
            'status_after' => 'perizinan',
        ]);
    }

    public function test_multi_lop_project_is_rejected_before_survey_mutation(): void
    {
        DB::table('lops')->insert([
            'id_lop' => 21,
            'project_id' => 10,
            'lop_name' => 'LOP B',
            'status_progress' => 'survey',
        ]);

        $this->actingAs(User::findOrFail(1))
            ->post(route('waspang.survey.map.confirm', 10))
            ->assertStatus(409);

        $this->assertDatabaseMissing('project_activity_logs', [
            'activity_type' => 'survey_map_confirmed',
        ]);
    }

    public function test_legacy_drm_status_is_presented_as_perizinan(): void
    {
        DB::table('lops')->where('id_lop', 20)->update([
            'status_progress' => 'drm',
        ]);

        $summary = Project::findOrFail(10)->progressSummary();

        $this->assertSame('perizinan', $summary['effectiveStageCode']);
        $this->assertSame('Perizinan', $summary['effectiveStageLabel']);
        $this->assertSame(
            ['survey', 'perizinan'],
            ProjectStage::sequential()->pluck('code')->all()
        );
    }

    private function seedBaseWorkflow(): void
    {
        DB::table('users')->insert([
            'id_user' => 1,
            'name' => 'Waspang Test',
            'username' => 'waspang-test',
            'password' => bcrypt('secret'),
            'role' => 'waspang',
        ]);
        DB::table('projects')->insert([
            'id_project' => 10,
            'customer_id' => 1,
            'project_name' => 'Project Test',
            'kml_file' => 'kml/admin.kml',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('pro_assign')->insert([
            'id_proassign' => 1,
            'project_id' => 10,
            'waspang_id' => 1,
        ]);
        DB::table('project_stages')->insert([
            [
                'id' => 1,
                'code' => 'survey',
                'label' => 'Survey',
                'phase_group' => 'persiapan',
                'sequence' => 2,
                'color' => null,
            ],
            [
                'id' => 2,
                'code' => 'drm',
                'label' => 'Proses DRM',
                'phase_group' => 'persiapan',
                'sequence' => 3,
                'color' => null,
            ],
            [
                'id' => 3,
                'code' => 'perizinan',
                'label' => 'Perizinan',
                'phase_group' => 'persiapan',
                'sequence' => 4,
                'color' => 'amber',
            ],
        ]);
        DB::table('lops')->insert([
            'id_lop' => 20,
            'project_id' => 10,
            'lop_name' => 'LOP A',
            'status_progress' => 'survey',
        ]);
        DB::table('designators')->insert([
            [
                'id_designator' => 30,
                'customer_id' => 1,
                'designator' => 'M-PAIR-1',
                'item_name' => 'Material Pair',
                'unit' => 'UNIT',
                'type' => 'material',
                'pair_code' => 'PAIR-1',
            ],
            [
                'id_designator' => 31,
                'customer_id' => 1,
                'designator' => 'J-PAIR-1',
                'item_name' => 'Jasa Pair',
                'unit' => 'UNIT',
                'type' => 'jasa',
                'pair_code' => 'PAIR-1',
            ],
            [
                'id_designator' => 32,
                'customer_id' => 1,
                'designator' => 'M-PAIR-2',
                'item_name' => 'Material Tambahan',
                'unit' => 'UNIT',
                'type' => 'material',
                'pair_code' => 'PAIR-2',
            ],
            [
                'id_designator' => 33,
                'customer_id' => 1,
                'designator' => 'J-PAIR-2',
                'item_name' => 'Jasa Tambahan',
                'unit' => 'UNIT',
                'type' => 'jasa',
                'pair_code' => 'PAIR-2',
            ],
        ]);
        DB::table('boq_items')->insert([
            [
                'id_boq' => 100,
                'project_id' => 10,
                'lop_id' => 20,
                'designator_id' => 30,
                'designator' => 'M-PAIR-1',
                'item_name' => 'Material Pair',
                'unit' => 'UNIT',
                'quantity_plan' => 8,
                'quantity_actual' => 0,
            ],
            [
                'id_boq' => 101,
                'project_id' => 10,
                'lop_id' => 20,
                'designator_id' => 31,
                'designator' => 'J-PAIR-1',
                'item_name' => 'Jasa Pair',
                'unit' => 'UNIT',
                'quantity_plan' => 8,
                'quantity_actual' => 0,
            ],
        ]);

        Storage::disk('public')->put('kml/admin.kml', '<kml></kml>');
    }

    private function createWorkflowSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
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
        Schema::create('projects', function (Blueprint $table) {
            $table->id('id_project');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('project_name');
            $table->string('kml_file')->nullable();
            $table->timestamps();
        });
        Schema::create('pro_assign', function (Blueprint $table) {
            $table->id('id_proassign');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('waspang_id');
            $table->timestamps();
        });
        Schema::create('project_stages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('phase_group')->nullable();
            $table->unsignedInteger('sequence')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_pause_type')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
        });
        Schema::create('lops', function (Blueprint $table) {
            $table->id('id_lop');
            $table->unsignedBigInteger('project_id');
            $table->string('lop_name');
            $table->string('status_progress');
            $table->timestamps();
        });
        Schema::create('designators', function (Blueprint $table) {
            $table->id('id_designator');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('designator');
            $table->string('item_name');
            $table->string('unit');
            $table->string('type')->nullable();
            $table->string('pair_code')->nullable();
            $table->timestamps();
        });
        Schema::create('boq_items', function (Blueprint $table) {
            $table->id('id_boq');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('lop_id');
            $table->unsignedBigInteger('designator_id')->nullable();
            $table->string('designator')->nullable();
            $table->text('item_name');
            $table->string('unit')->nullable();
            $table->integer('quantity_plan')->nullable();
            $table->integer('quantity_actual')->nullable();
        });
        Schema::create('evidences', function (Blueprint $table) {
            $table->id('id_evidence');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->timestamps();
        });
        Schema::create('lop_measurement_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('item_key');
            $table->boolean('is_not_applicable')->default(false);
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->timestamps();
        });
        Schema::create('site_surveys', function (Blueprint $table) {
            $table->id('id_site_surveys');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->string('title');
            $table->unsignedBigInteger('surveyor_id');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->decimal('ending_site_lat', 10, 7)->nullable();
            $table->decimal('ending_site_lng', 10, 7)->nullable();
            $table->string('ending_site_name')->nullable();
            $table->string('kml_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('project_activity_logs', function (Blueprint $table) {
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
    }
}
