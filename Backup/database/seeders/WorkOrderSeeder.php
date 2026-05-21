<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLabor;
use App\Models\Customer;
use App\Models\Package;
use App\Models\PackageSize;
use App\Models\Item;
use Carbon\Carbon;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have customers
        $customer1 = Customer::firstOrCreate(
            ['name' => 'Auto Service Center Jakarta'],
            [
                'code' => 'ASC001',
                'address' => 'Jl. Sudirman No. 456, Jakarta',
                'phone' => '021-5555-5678',
                'is_active' => true
            ]
        );

        $customer2 = Customer::firstOrCreate(
            ['name' => 'PT Premium Car Care'],
            [
                'code' => 'PCC001',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'phone' => '021-7777-8888',
                'is_active' => true
            ]
        );

        $customer3 = Customer::firstOrCreate(
            ['name' => 'Budi Santoso'],
            [
                'code' => 'BS001',
                'address' => 'Jl. Kebon Jeruk No. 123, Jakarta',
                'phone' => '081234567890',
                'is_active' => true
            ]
        );

        // Get some items for materials
        $paintItem = Item::where('code', 'PAINT-001')->first();
        $sandpaperItem = Item::where('code', 'SAND-001')->first();

        // Get packages
        $clsPackage = Package::where('code', 'CLS')->first(); // Classic Package with Size S/M/L/XL/XXL
        $indPackage = Package::where('code', 'IND')->first(); // Interior Detailing with 2 Row/3 Row
        $sfdPackage = Package::where('code', 'SFD')->first(); // Salon Full Detailing with All
        $sptPackage = Package::where('code', 'SPT')->first(); // Sport Package with Size S/M/L/XL/XXL

        // ===== Work Order 1: Size S (Small Car) =====
        if ($clsPackage) {
            $sizeS = $clsPackage->sizes()->where('size_name', 'Size S')->first();
            if ($sizeS && !WorkOrder::where('wo_number', '2601/HAS/001')->exists()) {
                $wo1 = WorkOrder::create([
                    'wo_number' => '2601/HAS/001',
                    'customer_id' => $customer1->id,
                    'package_id' => $clsPackage->id,
                    'package_size_id' => $sizeS->id,
                    'account_code' => 'C',
                    'work_date' => Carbon::parse('2026-02-20'),
                    'deadline' => Carbon::parse('2026-02-25'),
                    'vehicle_info' => 'Toyota Agya',
                    'vehicle_merk' => 'Toyota',
                    'vehicle_type_year' => 'Agya / 2023',
                    'vehicle_plate' => 'B 1234 ABC',
                    'vehicle_km' => 15000,
                    'chasis_no' => 'MHKA123456789',
                    'paket_code' => 'CLS',
                    'paket_name' => 'Classic Package',
                    'paket_size' => 'Size S',
                    'paket_grand_total' => $sizeS->price + 75000,
                    'description' => 'Coating application for small car',
                    'status' => 'on_progress',
                    'material_total' => 0,
                    'labor_total' => 75000,
                    'grand_total' => $sizeS->price + 75000,
                    'sa_sales' => 'Angga',
                    'created_by' => 1,
                ]);

                if ($paintItem) {
                    WorkOrderItem::create([
                        'work_order_id' => $wo1->id,
                        'item_id' => $paintItem->id,
                        'quantity' => 100,
                        'remark' => 'Base coat',
                    ]);
                }

                WorkOrderLabor::create([
                    'work_order_id' => $wo1->id,
                    'description' => 'Surface preparation',
                    'qty' => 1,
                    'remarks' => 'Clean and polish surface',
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo1->id,
                    'description' => 'Coating application',
                    'qty' => 1,
                    'remarks' => 'Applied 2 layers ceramic coating',
                ]);
            }
        }

        // ===== Work Order 2: Size XL (Large SUV) =====
        if ($sptPackage) {
            $sizeXL = $sptPackage->sizes()->where('size_name', 'Size XL')->first();
            if ($sizeXL && !WorkOrder::where('wo_number', '2602/HAS/001')->exists()) {
                $wo2 = WorkOrder::create([
                    'wo_number' => '2602/HAS/001',
                    'customer_id' => $customer2->id,
                    'package_id' => $sptPackage->id,
                    'package_size_id' => $sizeXL->id,
                    'account_code' => 'C',
                    'work_date' => Carbon::parse('2026-02-24'),
                    'deadline' => Carbon::parse('2026-02-26'),
                    'vehicle_info' => 'Toyota Fortuner',
                    'vehicle_merk' => 'Toyota',
                    'vehicle_type_year' => 'Fortuner VRZ / 2024',
                    'vehicle_plate' => 'B 5678 XYZ',
                    'vehicle_km' => 8500,
                    'chasis_no' => 'MHFG987654321',
                    'paket_code' => 'SPT',
                    'paket_name' => 'Sport Package',
                    'paket_size' => 'Size XL',
                    'paket_grand_total' => $sizeXL->price + 75000,
                    'description' => 'Sport coating package for large SUV',
                    'status' => 'on_progress',
                    'material_total' => 0,
                    'labor_total' => 75000,
                    'grand_total' => $sizeXL->price + 75000,
                    'sa_sales' => 'Felix',
                    'created_by' => 1,
                ]);

                if ($sandpaperItem) {
                    WorkOrderItem::create([
                        'work_order_id' => $wo2->id,
                        'item_id' => $sandpaperItem->id,
                        'quantity' => 5,
                        'remark' => 'Fine grit for finishing',
                    ]);
                }

                WorkOrderLabor::create([
                    'work_order_id' => $wo2->id,
                    'description' => 'Paint correction',
                    'qty' => 1,
                    'remarks' => 'Remove swirl marks and scratches',
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo2->id,
                    'description' => 'Ceramic coating application',
                    'qty' => 1,
                    'remarks' => 'Sport coating with enhanced gloss',
                ]);
            }
        }

        // ===== Work Order 3: 2 Row (Interior Detailing) =====
        if ($indPackage) {
            $size2Row = $indPackage->sizes()->where('size_name', '2 Row')->first();
            if ($size2Row && !WorkOrder::where('wo_number', '2602/HAS/002')->exists()) {
                $wo3 = WorkOrder::create([
                    'wo_number' => '2602/HAS/002',
                    'customer_id' => $customer3->id,
                    'package_id' => $indPackage->id,
                    'package_size_id' => $size2Row->id,
                    'account_code' => 'C',
                    'work_date' => Carbon::parse('2026-02-23'),
                    'deadline' => Carbon::parse('2026-02-24'),
                    'vehicle_info' => 'Honda Civic',
                    'vehicle_merk' => 'Honda',
                    'vehicle_type_year' => 'Civic RS / 2023',
                    'vehicle_plate' => 'B 9876 DEF',
                    'vehicle_km' => 12000,
                    'chasis_no' => 'JHM123456789',
                    'paket_code' => 'IND',
                    'paket_name' => 'Interior Detailing',
                    'paket_size' => '2 Row',
                    'paket_grand_total' => $size2Row->price + 75000,
                    'description' => 'Deep cleaning interior for 2-row sedan',
                    'status' => 'on_progress',
                    'material_total' => 0,
                    'labor_total' => 75000,
                    'grand_total' => $size2Row->price + 75000,
                    'sa_sales' => 'Rina',
                    'created_by' => 1,
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo3->id,
                    'description' => 'Vacuum and shampoo seats',
                    'qty' => 1,
                    'remarks' => 'Deep clean fabric seats',
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo3->id,
                    'description' => 'Dashboard and trim cleaning',
                    'qty' => 1,
                    'remarks' => 'UV protection applied',
                ]);
            }
        }

        // ===== Work Order 4: 3 Row (Interior Detailing) =====
        if ($indPackage) {
            $size3Row = $indPackage->sizes()->where('size_name', '3 Row')->first();
            if ($size3Row && !WorkOrder::where('wo_number', '2602/HAS/003')->exists()) {
                $wo4 = WorkOrder::create([
                    'wo_number' => '2602/HAS/003',
                    'customer_id' => $customer1->id,
                    'package_id' => $indPackage->id,
                    'package_size_id' => $size3Row->id,
                    'account_code' => 'INT_WS',
                    'work_date' => Carbon::parse('2026-02-24'),
                    'deadline' => Carbon::parse('2026-02-25'),
                    'vehicle_info' => 'Mitsubishi Pajero Sport',
                    'vehicle_merk' => 'Mitsubishi',
                    'vehicle_type_year' => 'Pajero Sport Dakar / 2024',
                    'vehicle_plate' => 'B 1111 GHI',
                    'vehicle_km' => 25000,
                    'chasis_no' => 'MMB987654321',
                    'paket_code' => 'IND',
                    'paket_name' => 'Interior Detailing',
                    'paket_size' => '3 Row',
                    'paket_grand_total' => $size3Row->price + 75000,
                    'description' => 'Complete interior detailing for 3-row SUV',
                    'status' => 'on_progress',
                    'material_total' => 0,
                    'labor_total' => 75000,
                    'grand_total' => $size3Row->price + 75000,
                    'sa_sales' => 'Doni',
                    'created_by' => 1,
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo4->id,
                    'description' => 'Deep cleaning all 3 rows',
                    'qty' => 1,
                    'remarks' => 'Leather treatment and conditioning',
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo4->id,
                    'description' => 'Carpet shampooing',
                    'qty' => 1,
                    'remarks' => 'Stain removal treatment applied',
                ]);
            }
        }

        // ===== Work Order 5: All (Salon Full Detailing) =====
        if ($sfdPackage) {
            $sizeAll = $sfdPackage->sizes()->where('size_name', 'All')->first();
            if ($sizeAll && !WorkOrder::where('wo_number', '2602/HAS/004')->exists()) {
                $wo5 = WorkOrder::create([
                    'wo_number' => '2602/HAS/004',
                    'customer_id' => $customer2->id,
                    'package_id' => $sfdPackage->id,
                    'package_size_id' => $sizeAll->id,
                    'account_code' => 'C',
                    'work_date' => Carbon::parse('2026-02-24'),
                    'deadline' => Carbon::parse('2026-02-26'),
                    'vehicle_info' => 'Mercedes-Benz E-Class',
                    'vehicle_merk' => 'Mercedes-Benz',
                    'vehicle_type_year' => 'E 300 / 2022',
                    'vehicle_plate' => 'T 0000 TE',
                    'vehicle_km' => 25331,
                    'chasis_no' => 'MHL2210000000',
                    'paket_code' => 'SFD',
                    'paket_name' => 'Salon Full Detailing',
                    'paket_size' => 'All',
                    'paket_grand_total' => $sizeAll->price + 75000,
                    'description' => 'Complete salon detailing service',
                    'status' => 'on_progress',
                    'material_total' => 0,
                    'labor_total' => 75000,
                    'grand_total' => $sizeAll->price + 75000,
                    'sa_sales' => 'Angga',
                    'created_by' => 1,
                ]);

                if ($paintItem) {
                    WorkOrderItem::create([
                        'work_order_id' => $wo5->id,
                        'item_id' => $paintItem->id,
                        'quantity' => 150,
                        'remark' => 'Test',
                    ]);
                }

                if ($sandpaperItem) {
                    WorkOrderItem::create([
                        'work_order_id' => $wo5->id,
                        'item_id' => $sandpaperItem->id,
                        'quantity' => 5,
                        'remark' => '',
                    ]);
                }

                WorkOrderLabor::create([
                    'work_order_id' => $wo5->id,
                    'description' => 'Pengecatan',
                    'qty' => 1,
                    'remarks' => '',
                ]);

                WorkOrderLabor::create([
                    'work_order_id' => $wo5->id,
                    'description' => 'Polishing',
                    'qty' => 1,
                    'remarks' => '',
                ]);
            }
        }

        $this->command->info('Work Orders seeded successfully!');
        $this->command->info('Created 5 work orders:');
        $this->command->info('- Size S (Small car - Toyota Agya)');
        $this->command->info('- Size XL (Large SUV - Toyota Fortuner)');
        $this->command->info('- 2 Row (Sedan - Honda Civic)');
        $this->command->info('- 3 Row (3-row SUV - Mitsubishi Pajero Sport)');
        $this->command->info('- All (Any size - Mercedes-Benz E-Class)');
    }
}
