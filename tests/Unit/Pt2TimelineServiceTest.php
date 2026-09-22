<?php

namespace Tests\Unit;

use App\Models\MancorePt2;
use App\Models\ProjectActivityLog;
use App\Models\Pt2Evidence;
use App\Models\Pt2Lop;
use App\Models\Pt2Project;
use App\Models\Role;
use App\Models\SurveyPt2;
use App\Models\User;
use App\Services\Pt2TimelineService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class Pt2TimelineServiceTest extends TestCase
{
    public function test_timeline_route_is_read_only_and_limited_to_management_roles(): void
    {
        $route = Route::getRoutes()->getByName('pt2.timeline');

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'role:admin,superadmin,super_tif,officer,pm,tif',
            $route->gatherMiddleware(),
        );
    }

    public function test_events_are_reconstructed_from_pt2_sources(): void
    {
        $lop = $this->lopWithRelations();
        $logs = collect([
            new ProjectActivityLog([
                'activity_type' => 'approve_evidence_pt2',
                'title' => 'Eviden PT2 Disetujui',
                'description' => 'Admin menyetujui eviden.',
                'stage' => 'instalasi',
                'evidence_id' => 10,
            ]),
        ]);
        $logs->first()->created_at = CarbonImmutable::parse('2026-09-03 10:00:00');

        $events = app(Pt2TimelineService::class)->buildEvents($lop, $logs);

        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'project_created_pt2'));
        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'survey_pt2'));
        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'upload_evidence_pt2'));
        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'approve_evidence_pt2'));
        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'mancore_pt2'));
        $this->assertTrue($events->contains(fn ($event) => $event['type'] === 'lop_golive_pt2'));
        $this->assertSame('project_created_pt2', $events->first()['type']);
    }

    public function test_stage_durations_use_available_milestones_without_inventing_missing_dates(): void
    {
        $stages = app(Pt2TimelineService::class)->buildStageDurations($this->lopWithRelations());

        $this->assertCount(6, $stages);
        $this->assertNotNull($stages->firstWhere('code', 'inisiasi')['entered_at']);
        $this->assertNotNull($stages->firstWhere('code', 'survey')['entered_at']);
        $this->assertNotNull($stages->firstWhere('code', 'instalasi')['entered_at']);
        $this->assertNull($stages->firstWhere('code', 'finishing')['entered_at']);
        $this->assertTrue($stages->firstWhere('code', 'golive')['is_current']);
    }

    public function test_stage_durations_normalize_string_timestamps_before_calculation(): void
    {
        $lop = $this->lopWithRelations();
        $this->useRawStringTimestamps($lop);

        $stages = app(Pt2TimelineService::class)->buildStageDurations($lop);

        $inisiasi = $stages->firstWhere('code', 'inisiasi');
        $survey = $stages->firstWhere('code', 'survey');

        $this->assertInstanceOf(CarbonImmutable::class, $inisiasi['entered_at']);
        $this->assertSame(90000, $inisiasi['duration_seconds']);
        $this->assertInstanceOf(CarbonImmutable::class, $survey['entered_at']);
        $this->assertIsInt($survey['duration_seconds']);
    }

    public function test_timeline_view_renders_event_colors_without_undefined_variables(): void
    {
        $role = new Role([
            'code' => 'admin',
            'name' => 'Admin',
        ]);
        $user = new User([
            'nik' => 'TEST-ADMIN',
            'name' => 'Admin Test',
            'username' => 'admin.test',
        ]);
        $user->setAttribute('id_user', 1);
        $user->setRelation('roleRef', $role);

        $this->actingAs($user);
        $this->withoutVite();
        $this->app['view']->share('errors', new ViewErrorBag);

        $lop = $this->lopWithRelations();
        $timeline = app(Pt2TimelineService::class);
        $events = $timeline->buildEvents($lop, collect());
        $stageDurations = $timeline->buildStageDurations($lop);
        $summary = [
            'badge' => 'bg-emerald-100 text-emerald-700',
            'stageLabel' => 'Golive',
            'progress' => 100,
        ];

        $html = view('pt2.timeline', compact(
            'events',
            'lop',
            'stageDurations',
            'summary',
        ))->render();

        $this->assertStringContainsString('Timeline Project PT 2', $html);
        $this->assertStringContainsString('LOP-PT2-001', $html);
        $this->assertStringContainsString('bg-blue-100 text-blue-700', $html);
        $this->assertStringContainsString('bg-emerald-100 text-emerald-700', $html);
    }

    private function lopWithRelations(): Pt2Lop
    {
        $project = new Pt2Project([
            'pid' => 'PID-PT2-001',
            'project_name' => 'Project PT 2 Test',
        ]);
        $project->created_at = CarbonImmutable::parse('2026-09-01 08:00:00');

        $lop = new Pt2Lop([
            'id_pt2_lop' => 1,
            'pt2_project_id' => 1,
            'lop_name' => 'LOP-PT2-001',
            'status_progress' => 'golive',
            'is_golive' => 1,
            'sdi_approval_status' => 'approved',
            'golive_evidence_path' => 'evidences/golive/pt2/uim.jpg',
        ]);
        $lop->created_at = CarbonImmutable::parse('2026-09-01 08:00:00');
        $lop->updated_at = CarbonImmutable::parse('2026-09-05 12:00:00');
        $lop->golive_at = CarbonImmutable::parse('2026-09-05 12:00:00');

        $survey = new SurveyPt2([
            'mode' => 'A',
            'pm_approval_status' => 'approved',
        ]);
        $survey->created_at = CarbonImmutable::parse('2026-09-02 09:00:00');
        $survey->updated_at = CarbonImmutable::parse('2026-09-02 09:00:00');

        $evidence = new Pt2Evidence([
            'id_pt2_evidence' => 10,
            'stage' => 'instalasi',
            'evidence_type' => 'material',
            'file_path' => 'evidences/pt2/material.jpg',
            'status' => 'approved',
        ]);
        $evidence->created_at = CarbonImmutable::parse('2026-09-03 09:00:00');

        $mancore = new MancorePt2([
            'odp_label' => 'ODP-01',
            'odc_label' => 'ODC-01',
        ]);
        $mancore->created_at = CarbonImmutable::parse('2026-09-04 11:00:00');

        $lop->setRelation('project', $project);
        $lop->setRelation('assignment', null);
        $lop->setRelation('surveys', collect([$survey]));
        $lop->setRelation('evidences', collect([$evidence]));
        $lop->setRelation('dismantles', collect());
        $lop->setRelation('mancores', collect([$mancore]));

        return $lop;
    }

    private function useRawStringTimestamps(Pt2Lop $lop): void
    {
        $models = collect([
            $lop,
            $lop->project,
            ...$lop->surveys,
            ...$lop->evidences,
            ...$lop->mancores,
        ]);

        $models->each(function ($model) {
            $model->timestamps = false;
            $attributes = $model->getAttributes();

            foreach (['created_at', 'updated_at', 'golive_at'] as $column) {
                if (isset($attributes[$column])) {
                    $attributes[$column] = CarbonImmutable::parse($attributes[$column])->format('Y-m-d H:i:s');
                }
            }

            $model->setRawAttributes($attributes, true);
        });
    }
}
