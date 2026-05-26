<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Designator;

class DesignatorSeeder extends Seeder
{
    public function run(): void
    {
        $items = [

            [
                'designator' => 'KU-FO-001',
                'item_name' => 'Kabel Fiber Optik',
                'unit' => 'm',
            ],

            [
                'designator' => 'CL-001',
                'item_name' => 'Closure',
                'unit' => 'titik',
            ],

            [
                'designator' => 'GL-001',
                'item_name' => 'Galian Kabel',
                'unit' => 'm',
            ],

            [
                'designator' => 'HH-001',
                'item_name' => 'Handhole',
                'unit' => 'unit',
            ],

            [
                'designator' => 'ODP-001',
                'item_name' => 'ODP Pole',
                'unit' => 'unit',
            ],

            [
                'designator' => 'ODC-001',
                'item_name' => 'ODC Cabinet',
                'unit' => 'unit',
            ],

            [
                'designator' => 'TIANG-001',
                'item_name' => 'Tiang Beton 7 Meter',
                'unit' => 'batang',
            ],

            [
                'designator' => 'TIANG-002',
                'item_name' => 'Tiang Besi 9 Meter',
                'unit' => 'batang',
            ],

            [
                'designator' => 'ACC-001',
                'item_name' => 'Aksesoris Joint Closure',
                'unit' => 'set',
            ],

            [
                'designator' => 'ACC-002',
                'item_name' => 'Aksesoris ODP',
                'unit' => 'set',
            ],

            [
                'designator' => 'OTB-001',
                'item_name' => 'OTB 24 Core',
                'unit' => 'unit',
            ],

            [
                'designator' => 'SPL-001',
                'item_name' => 'Splitter 1:8',
                'unit' => 'unit',
            ],

            [
                'designator' => 'SPL-002',
                'item_name' => 'Splitter 1:16',
                'unit' => 'unit',
            ],

            [
                'designator' => 'PIG-001',
                'item_name' => 'Pigtail SC/APC',
                'unit' => 'pcs',
            ],

            [
                'designator' => 'PATCH-001',
                'item_name' => 'Patchcord SC/APC',
                'unit' => 'pcs',
            ],

        ];

        foreach ($items as $item) {

            Designator::create($item);
        }
    }
}