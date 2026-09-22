<?php

namespace Tests\Unit;

use App\Models\LopGoliveSubmission;
use PHPUnit\Framework\TestCase;

class LopGoliveSubmissionTest extends TestCase
{
    public function test_draft_is_editable_and_submitted_submission_is_locked(): void
    {
        $draft = new LopGoliveSubmission([
            'submission_status' => LopGoliveSubmission::STATUS_DRAFT,
        ]);
        $submitted = new LopGoliveSubmission([
            'submission_status' => LopGoliveSubmission::STATUS_SUBMITTED,
        ]);

        $this->assertFalse($draft->isSubmitted());
        $this->assertFalse($draft->isLocked());
        $this->assertTrue($submitted->isSubmitted());
        $this->assertTrue($submitted->isLocked());
    }

    public function test_four_document_categories_are_required_before_submit(): void
    {
        $submission = new LopGoliveSubmission([
            'capture_valins_paths' => ['valins.jpg'],
            'abd_valid4_paths' => ['abd.pdf'],
            'kml_paths' => ['route.kml'],
        ]);

        $this->assertFalse($submission->isComplete());

        $submission->mancore_paths = ['mancore.xlsx'];

        $this->assertTrue($submission->isComplete());
    }
}
