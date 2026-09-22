<?php

namespace Tests\Unit;

use App\Services\DeploymentMovementSummaryService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DeploymentMovementSummaryServiceTest extends TestCase
{
    #[DataProvider('goliveMovementStages')]
    public function test_golive_activity_is_classified_to_the_correct_movement_stage(string $activityType, string $expectedStage): void
    {
        $log = (object) [
            'stage' => null,
            'meta' => null,
            'status_after' => null,
            'activity_type' => $activityType,
            'status_progress' => 'finishing',
        ];
        $stages = collect([
            'fi_ogp_golive' => ['label' => 'FI-OGP Golive'],
            'golive' => ['label' => 'Golive'],
            'activity_other' => ['label' => 'Aktivitas Lainnya'],
        ]);

        $method = new ReflectionMethod(DeploymentMovementSummaryService::class, 'activityStageCode');
        $actual = $method->invoke(new DeploymentMovementSummaryService, $log, new Collection, $stages);

        $this->assertSame($expectedStage, $actual);
    }

    /** @return array<string, array{string, string}> */
    public static function goliveMovementStages(): array
    {
        return [
            'admin draft upload moves FI-OGP' => ['golive_submission_draft_upload', 'fi_ogp_golive'],
            'admin final submit moves FI-OGP' => ['golive_submission_submitted', 'fi_ogp_golive'],
            'SDI UIM upload moves Golive' => ['golive_verification_upload', 'golive'],
        ];
    }
}
