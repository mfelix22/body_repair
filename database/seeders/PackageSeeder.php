<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\PackageSize;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packagesData = [
            'PAKET ALA-CARTE' => [
                'SFD'    => ['name' => 'Salon Full Detailing',     'sizes' => ['All' => 650000]],
                'PPW'    => ['name' => 'Polish & Wax',             'sizes' => ['Size S' => 1650000, 'Size M' => 1750000, 'Size L' => 1850000, 'Size XL' => 1950000, 'Size XXL' => 2050000]],
                'EGD'    => ['name' => 'Engine Detailing',         'sizes' => ['All' => 500000]],
                'WSH'    => ['name' => 'Premium Wash Wax',         'sizes' => ['All' => 350000]],
                'UND'    => ['name' => 'Undercarriage',            'sizes' => ['All' => 1100000]],
                'EXD'    => ['name' => 'Exterior Detailing',       'sizes' => ['All' => 1250000]],
                'IND'    => ['name' => 'Interior Detailing',       'sizes' => ['2 Row' => 1100000, '3 Row' => 1350000]],
                'GCW'    => ['name' => 'Glass Coating',            'sizes' => ['All' => 1000000]],
                'ESD'    => ['name' => 'Truck Express Cleaning',   'sizes' => ['All' => 900000]],
            ],
            'PAKET COATING' => [
                'CLS'    => ['name' => 'Classic Package',          'sizes' => ['Size S' => 3000000, 'Size M' => 3250000, 'Size L' => 3500000, 'Size XL' => 3750000, 'Size XXL' => 4000000]],
                'SPT'    => ['name' => 'Sport Package',            'sizes' => ['Size S' => 3300000, 'Size M' => 3550000, 'Size L' => 3800000, 'Size XL' => 4050000, 'Size XXL' => 4300000]],
                'ELG'    => ['name' => 'Elegance Package',         'sizes' => ['Size S' => 4500000, 'Size M' => 4750000, 'Size L' => 5000000, 'Size XL' => 5250000, 'Size XXL' => 5500000]],
                'AVG'    => ['name' => 'Avantgarde Package',       'sizes' => ['Size S' => 6250000, 'Size M' => 6500000, 'Size L' => 6750000, 'Size XL' => 7000000, 'Size XXL' => 7250000]],
            ],
            'MAINTENANCE COATING' => [
                'MAIN-1' => ['name' => 'Level 1',                  'sizes' => ['All' => 550000]],
                'MAIN-2' => ['name' => 'Level 2',                  'sizes' => ['All' => 950000]],
                'MAIN-3' => ['name' => 'Level 3',                  'sizes' => ['All' => 1100000]],
            ],
            'BUNDLING WORKSHOP & BODY REPAIR' => [
                'BONUS-BP' => ['name' => 'Coating Bonus Body Repair', 'sizes' => ['All' => 1300000]],
            ],
        ];

        foreach ($packagesData as $category => $packages) {
            foreach ($packages as $code => $packageInfo) {
                $package = Package::create([
                    'category' => $category,
                    'code' => $code,
                    'name' => $packageInfo['name'],
                    'is_active' => true,
                ]);

                foreach ($packageInfo['sizes'] as $sizeName => $price) {
                    PackageSize::create([
                        'package_id' => $package->id,
                        'size_name' => $sizeName,
                        'price' => $price,
                        'is_active' => true,
                    ]);
                }
            }
        }

        $this->command->info('Packages and sizes seeded successfully!');
    }
}
