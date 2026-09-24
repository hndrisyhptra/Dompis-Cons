<?php

namespace Tests\Feature;

use App\Models\Designator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DesignatorProgressCategoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id('id_customer');
            $table->string('customer_code');
            $table->string('customer_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('designators', function (Blueprint $table) {
            $table->id('id_designator');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('designator', 100);
            $table->string('item_name', 500);
            $table->string('unit', 50);
            $table->string('type')->nullable();
            $table->string('pair_code', 100)->nullable();
            $table->string('progress_category', 50)->default('OTHER');
            $table->boolean('requires_finishing_evidence')->default(false);
            $table->timestamps();
        });

        $this->app['db']->table('customers')->insert([
            'id_customer' => 1,
            'customer_code' => 'TIF',
            'customer_name' => 'TIF',
            'is_active' => true,
        ]);
    }

    public function test_galian_is_available_as_a_progress_category(): void
    {
        $this->assertContains('GALIAN', Designator::PROGRESS_CATEGORIES);
    }

    public function test_designator_category_can_be_updated_to_galian(): void
    {
        $designator = Designator::create([
            'customer_id' => 1,
            'designator' => 'J-GALIAN-001',
            'item_name' => 'Pekerjaan Galian',
            'unit' => 'M',
            'type' => 'jasa',
            'pair_code' => 'GALIAN-001',
            'progress_category' => 'OTHER',
        ]);

        $response = $this->withoutMiddleware()
            ->from(route('designators.index'))
            ->put(route('designators.update', $designator->id_designator), [
                'customer_id' => 1,
                'designator' => 'J-GALIAN-001',
                'item_name' => 'Pekerjaan Galian',
                'unit' => 'M',
                'type' => 'jasa',
                'pair_code' => 'GALIAN-001',
                'progress_category' => 'GALIAN',
            ]);

        $response->assertRedirect(route('designators.index'));

        $this->assertDatabaseHas('designators', [
            'id_designator' => $designator->id_designator,
            'progress_category' => 'GALIAN',
        ]);
    }
}
