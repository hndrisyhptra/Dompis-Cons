<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'customer_code' => 'TIF',
                'customer_name' => 'Telkom Infrastructure',
            ],
            [
                'customer_code' => 'MITRATEL',
                'customer_name' => 'Mitratel',
            ],
            [
                'customer_code' => 'MYREP',
                'customer_name' => 'MyRepublic',
            ],
            [
                'customer_code' => 'ASIANET',
                'customer_name' => 'AsiaNet',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['customer_code' => $customer['customer_code']],
                $customer + ['is_active' => true]
            );
        }
    }
}
