<?php

namespace Tests\Unit;

use App\Models\BoqItem;
use App\Models\BoqSurveyRound;
use App\Models\Designator;
use App\Models\Lop;
use App\Models\LopGoliveSubmission;
use App\Models\PermitCategory;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\LopSCurveService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LopSCurveServiceTest extends TestCase
{
    public function test_curve_route_is_read_only_and_limited_to_management_roles(): void
    {
        $route = Route::getRoutes()->getByName('lops.s-curve');

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'role:superadmin,admin,tif,super_tif,officer,pm',
            $route->gatherMiddleware(),
        );
    }

    public function test_duration_uses_calendar_days_and_counts_pair_code_once(): void
    {
        $lop = $this->lopWithRelations('MATARAM', 'MTR', [
            $this->boq(1, 'M-FO', 'material', 'FO', 'KABEL', 2500),
            $this->boq(2, 'J-FO', 'jasa', 'FO', 'KABEL', 2500),
            $this->boq(3, 'M-POLE', 'material', 'POLE', 'TIANG', 14),
            $this->boq(4, 'J-POLE', 'jasa', 'POLE', 'TIANG', 14),
            $this->boq(5, 'M-DIG', 'material', 'DIG', 'OTHER', 150),
            $this->boq(6, 'J-DIG', 'jasa', 'DIG', 'GALIAN', 150),
        ]);

        $curve = app(LopSCurveService::class)->build($lop, collect());

        $this->assertTrue($curve['ready']);
        $this->assertSame('BOQ Plan', $curve['inputs']['boq_source']);
        $this->assertSame(2500.0, $curve['inputs']['volumes']['KABEL']);
        $this->assertSame(14.0, $curve['inputs']['volumes']['TIANG']);
        $this->assertSame(150.0, $curve['inputs']['volumes']['GALIAN']);
        $this->assertSame(6, $curve['inputs']['installation_days']);
        $this->assertSame(41, $curve['inputs']['total_days']);
        $this->assertSame('2026-10-08', $curve['target']['survey_end']->format('Y-m-d'));
        $this->assertSame('2026-11-08', $curve['target']['planned_fi_end']->format('Y-m-d'));
        $this->assertNull($curve['target']['golive_target']);
        $this->assertSame(92.68, $curve['target']['series']->last()['progress']);
    }

    public function test_golive_target_starts_three_days_after_fi_requirements_are_complete(): void
    {
        $lop = $this->lopWithRelations('MATARAM', 'MTR', []);
        $lop->setRelation('goliveSubmission', new LopGoliveSubmission([
            'fi_completed_at' => '2026-11-20 16:00:00',
        ]));

        $curve = app(LopSCurveService::class)->build($lop, collect());

        $this->assertSame('2026-11-20', $curve['target']['fi_completed_at']->format('Y-m-d'));
        $this->assertSame('2026-11-23', $curve['target']['golive_target']->format('Y-m-d'));
        $this->assertSame(100.0, $curve['target']['series']->last()['progress']);
    }

    public function test_curve_is_not_calculated_when_start_date_is_missing(): void
    {
        $lop = $this->lopWithRelations('MATARAM', 'MTR', []);
        $lop->start_tgl = null;

        $curve = app(LopSCurveService::class)->build($lop, collect());

        $this->assertFalse($curve['ready']);
        $this->assertNull($curve['inputs']['total_days']);
        $this->assertTrue($curve['warnings']->contains(
            fn (string $warning) => str_contains($warning, 'start_tgl')
        ));
    }

    public function test_realization_uses_completed_milestones_without_inventing_later_progress(): void
    {
        $lop = $this->lopWithRelations('MATARAM', 'MTR', []);
        $lop->perizinan_completed_at = '2026-10-18 09:00:00';
        $lop->setRelation('surveyRounds', collect([
            new BoqSurveyRound([
                'round_number' => 1,
                'status' => 'completed',
                'finished_at' => '2026-10-07 17:00:00',
            ]),
        ]));

        $curve = app(LopSCurveService::class)->build($lop, collect());

        $this->assertCount(3, $curve['realization']);
        $this->assertSame('2026-10-18', $curve['realization']->last()['date']);
        $this->assertSame(80.0, $curve['realization']->last()['progress']);
        $this->assertStringContainsString('Perizinan selesai', $curve['realization']->last()['label']);
    }

    public function test_curve_view_renders_the_calculation_summary(): void
    {
        $role = new Role(['code' => 'admin', 'name' => 'Admin']);
        $user = new User([
            'nik' => 'TEST-ADMIN',
            'name' => 'Admin Test',
            'username' => 'admin.test',
        ]);
        $user->id_user = 1;
        $user->setRelation('roleRef', $role);
        $this->actingAs($user);
        $this->withoutVite();
        $this->app['view']->share('errors', new ViewErrorBag);

        $lop = $this->lopWithRelations('FLORES', 'END', [
            $this->boq(1, 'M-DIG', 'material', 'DIG', 'GALIAN', 150),
            $this->boq(2, 'J-DIG', 'jasa', 'DIG', 'GALIAN', 150),
        ]);
        $curveData = app(LopSCurveService::class)->build($lop, collect());

        $html = view('lops.s-curve', compact('curveData', 'lop'))->render();

        $this->assertStringContainsString('Kurva-S Target &amp; Realisasi', $html);
        $this->assertStringContainsString('Kupang Outer/Flores', $html);
        $this->assertStringContainsString('Dasar Perhitungan Instalasi', $html);
    }

    #[DataProvider('materialDeliveryProvider')]
    public function test_material_delivery_mapping(string $branch, string $sto, ?int $days): void
    {
        $rule = app(LopSCurveService::class)->deliveryRule($branch, $sto);

        $this->assertSame($days, $rule['days']);
    }

    public static function materialDeliveryProvider(): array
    {
        return [
            'Mataram inner' => ['MATARAM', 'MTR', 4],
            'Mataram outer' => ['MATARAM', 'SBW', 14],
            'Kupang inner' => ['KUPANG', 'KPN', 4],
            'Kupang outer' => ['KUPANG', 'SEB', 14],
            'Flores outer' => ['FLORES', 'END', 14],
            'Flores unmapped' => ['FLORES', 'KAI', null],
            'Surakarta all STO' => ['SURAKARTA', 'SOC', 3],
            'Bali all STO' => ['DENPASAR', 'DPS', 3],
        ];
    }

    /** @param array<int, BoqItem> $boqItems */
    private function lopWithRelations(string $branch, string $sto, array $boqItems): Lop
    {
        $project = new Project([
            'pid' => 'PID-PT3-001',
            'project_name' => 'Project PT3 Test',
            'program' => 'OSP-FTTH',
        ]);
        $project->id_project = 10;
        $project->setRelation('evidences', collect());

        $lop = new Lop([
            'project_id' => 10,
            'lop_name' => 'LOP-PT3-001',
            'branch' => $branch,
            'sto' => $sto,
            'start_tgl' => '2026-10-01',
        ]);
        $lop->id_lop = 20;
        $lop->setRelation('project', $project);
        $lop->setRelation('boqItems', collect($boqItems));
        $lop->setRelation('permitCategory', new PermitCategory([
            'name' => 'PERIZINAN PU NASIONAL',
        ]));
        $lop->setRelation('surveyRounds', collect());
        $lop->setRelation('stageHistories', collect());
        $lop->setRelation('measurementChecks', collect());
        $lop->setRelation('goliveSubmission', null);
        $project->setRelation('lops', collect([$lop]));

        return $lop;
    }

    private function boq(
        int $id,
        string $code,
        string $type,
        string $pairCode,
        string $category,
        float $plan,
    ): BoqItem {
        $designator = new Designator([
            'designator' => $code,
            'item_name' => $code.' item',
            'unit' => 'UNIT',
            'type' => $type,
            'pair_code' => $pairCode,
            'progress_category' => $category,
        ]);
        $designator->id_designator = $id + 100;

        $boq = new BoqItem([
            'project_id' => 10,
            'lop_id' => 20,
            'designator_id' => $designator->id_designator,
            'designator' => $code,
            'item_name' => $code.' item',
            'unit' => 'UNIT',
            'quantity_plan' => $plan,
        ]);
        $boq->id_boq = $id;
        $boq->setRelation('designatorData', $designator);
        $boq->setRelation('designatorDataByCode', null);

        return $boq;
    }
}
