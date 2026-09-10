<?php

namespace Tests\Unit;

use App\Models\BoqItem;
use App\Models\Designator;
use App\Services\SurveyPreparationService;
use PHPUnit\Framework\TestCase;

class SurveyPreparationServiceTest extends TestCase
{
    public function test_material_and_service_with_same_pair_code_are_shown_once(): void
    {
        $material = $this->boq(10, 'M-ODP-001', 'material', 'ODP-001', 8, 6);
        $service = $this->boq(11, 'J-ODP-001', 'jasa', 'ODP-001', 8, 0);

        $groups = (new SurveyPreparationService)->groupBoqItems(collect([$service, $material]));

        $this->assertCount(1, $groups);
        $this->assertSame('M-ODP-001', $groups->first()['designator']);
        $this->assertSame([10, 11], $groups->first()['item_ids']);
        $this->assertSame(8, $groups->first()['quantity_plan']);
        $this->assertSame(6, $groups->first()['quantity_actual']);
    }

    public function test_items_without_pair_code_remain_separate(): void
    {
        $first = $this->boq(20, 'M-A', 'material', null, 1, 0);
        $second = $this->boq(21, 'J-A', 'jasa', null, 1, 0);

        $groups = (new SurveyPreparationService)->groupBoqItems(collect([$first, $second]));

        $this->assertCount(2, $groups);
    }

    public function test_additional_pair_is_marked_additional_when_all_plans_are_null(): void
    {
        $material = $this->boq(30, 'M-NEW', 'material', 'NEW', null, 4);
        $service = $this->boq(31, 'J-NEW', 'jasa', 'NEW', null, 4);

        $group = (new SurveyPreparationService)->groupBoqItems(collect([$material, $service]))->first();

        $this->assertTrue($group['is_additional']);
        $this->assertNull($group['quantity_plan']);
    }

    private function boq(
        int $id,
        string $code,
        string $type,
        ?string $pairCode,
        ?int $plan,
        int $actual
    ): BoqItem {
        $designator = new Designator([
            'designator' => $code,
            'item_name' => $code.' item',
            'unit' => 'UNIT',
            'type' => $type,
            'pair_code' => $pairCode,
        ]);
        $designator->id_designator = $id + 100;

        $boq = new BoqItem([
            'designator_id' => $designator->id_designator,
            'designator' => $code,
            'item_name' => $code.' item',
            'unit' => 'UNIT',
            'quantity_plan' => $plan,
            'quantity_actual' => $actual,
        ]);
        $boq->id_boq = $id;
        $boq->setRelation('designatorData', $designator);

        return $boq;
    }
}
