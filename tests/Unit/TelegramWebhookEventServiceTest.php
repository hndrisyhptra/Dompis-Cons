<?php

namespace Tests\Unit;

use App\Jobs\PushTelegramWebhookEventJob;
use App\Models\User;
use App\Services\TelegramWebhookEventService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelegramWebhookEventServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Schema::dropIfExists('telegram_webhook_events');
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

    public function test_new_flow_events_have_consistent_recipients_and_payloads(): void
    {
        $admin = $this->user(10, 'Admin Area', 'admin');
        $waspang = $this->user(20, 'Waspang Area', 'waspang');

        $review = TelegramWebhookEventService::publishStageReviewRequested(
            $admin,
            'PT3',
            'survey',
            'Survey',
            100,
            200,
            'Project Test',
            'LOP Test',
            $waspang,
        );
        $sdi = TelegramWebhookEventService::publishSdiVerificationRequested(
            'PT3',
            100,
            200,
            'Project Test',
            'LOP Test',
            $admin,
        );
        $golive = TelegramWebhookEventService::publishProjectGolive(
            $waspang,
            'PT3',
            100,
            200,
            'Project Test',
            'LOP Test',
        );

        $this->assertSame('stage_review_requested', $review->event_type);
        $this->assertSame(10, $review->recipient_user_id);
        $this->assertSame('survey', $review->payload['stage_code']);
        $this->assertSame(1, $review->payload['event_version']);

        $this->assertSame('sdi_verification_requested', $sdi->event_type);
        $this->assertSame('role', $sdi->recipient_type);
        $this->assertSame('sdi', $sdi->recipient_role);

        $this->assertSame('project_golive', $golive->event_type);
        $this->assertSame(20, $golive->recipient_user_id);
        $this->assertSame('golive', $golive->payload['stage_code']);

        Queue::assertPushed(PushTelegramWebhookEventJob::class, 3);
    }

    private function user(int $id, string $name, string $role): User
    {
        $user = new User;
        $user->setRawAttributes([
            'id_user' => $id,
            'name' => $name,
            'username' => str($name)->slug('.')->toString(),
            'role' => $role,
        ], true);

        return $user;
    }
}
