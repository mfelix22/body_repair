<?php

namespace Database\Seeders;

use App\Models\UOM;
use App\Models\UOMConversion;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        // Create UOMs (Units of Measurement)
        $litre = UOM::create(['name' => 'Litre', 'code' => 'LTR']);
        $millilitre = UOM::create(['name' => 'Millilitre', 'code' => 'ML']);
        $kilogram = UOM::create(['name' => 'Kilogram', 'code' => 'KG']);
        $gram = UOM::create(['name' => 'Gram', 'code' => 'G']);
        $piece = UOM::create(['name' => 'Piece', 'code' => 'PC']);
        $meter = UOM::create(['name' => 'Meter', 'code' => 'M']);
        $centimeter = UOM::create(['name' => 'Centimeter', 'code' => 'CM']);

        // Create Items
        $paint = Item::create([
            'code' => 'PAINT-001',
            'name' => 'Automotive Paint - Red',
            'description' => 'High quality automotive paint for body repair',
            'category' => 'Paint',
            'smallest_uom_id' => $millilitre->id,
            'reorder_level' => 500,
            'is_active' => true,
        ]);

        $primer = Item::create([
            'code' => 'PRIMER-001',
            'name' => 'Automotive Primer',
            'description' => 'Surface primer for better paint adhesion',
            'category' => 'Paint',
            'smallest_uom_id' => $millilitre->id,
            'reorder_level' => 300,
            'is_active' => true,
        ]);

        $sandpaper = Item::create([
            'code' => 'SAND-001',
            'name' => 'Sandpaper Grit 120',
            'description' => 'Coarse sandpaper for surface preparation',
            'category' => 'Supplies',
            'smallest_uom_id' => $piece->id,
            'reorder_level' => 20,
            'is_active' => true,
        ]);

        $filler = Item::create([
            'code' => 'FILLER-001',
            'name' => 'Body Filler',
            'description' => 'Two-part epoxy body filler',
            'category' => 'Supplies',
            'smallest_uom_id' => $kilogram->id,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        // Create Item UOMs with prices
        ItemUOM::create([
            'item_id' => $paint->id,
            'uom_id' => $millilitre->id,
            'conversion_to_smallest' => 1,
            'price' => 0.50,
            'is_default' => true,
        ]);

        ItemUOM::create([
            'item_id' => $paint->id,
            'uom_id' => $litre->id,
            'conversion_to_smallest' => 1000,
            'price' => 400,
            'is_default' => false,
        ]);

        ItemUOM::create([
            'item_id' => $primer->id,
            'uom_id' => $millilitre->id,
            'conversion_to_smallest' => 1,
            'price' => 0.40,
            'is_default' => true,
        ]);

        ItemUOM::create([
            'item_id' => $primer->id,
            'uom_id' => $litre->id,
            'conversion_to_smallest' => 1000,
            'price' => 300,
            'is_default' => false,
        ]);

        ItemUOM::create([
            'item_id' => $sandpaper->id,
            'uom_id' => $piece->id,
            'conversion_to_smallest' => 1,
            'price' => 15,
            'is_default' => true,
        ]);

        ItemUOM::create([
            'item_id' => $filler->id,
            'uom_id' => $kilogram->id,
            'conversion_to_smallest' => 1,
            'price' => 120,
            'is_default' => true,
        ]);

        ItemUOM::create([
            'item_id' => $filler->id,
            'uom_id' => $gram->id,
            'conversion_to_smallest' => 0.001,
            'price' => 0.15,
            'is_default' => false,
        ]);

        // Create Stock
        Stock::create([
            'item_id' => $paint->id,
            'location' => 'default',
            'quantity' => 5000, // 5 litres in millilitres
        ]);

        Stock::create([
            'item_id' => $primer->id,
            'location' => 'default',
            'quantity' => 3000, // 3 litres in millilitres
        ]);

        Stock::create([
            'item_id' => $sandpaper->id,
            'location' => 'default',
            'quantity' => 50,
        ]);

        Stock::create([
            'item_id' => $filler->id,
            'location' => 'default',
            'quantity' => 25, // 25 kilograms
        ]);

        // Create UOM Conversions
        UOMConversion::create([
            'from_uom_id' => $litre->id,
            'to_uom_id' => $millilitre->id,
            'conversion_factor' => 1000,
        ]);

        UOMConversion::create([
            'from_uom_id' => $kilogram->id,
            'to_uom_id' => $gram->id,
            'conversion_factor' => 1000,
        ]);

        UOMConversion::create([
            'from_uom_id' => $meter->id,
            'to_uom_id' => $centimeter->id,
            'conversion_factor' => 100,
        ]);

        // Create Customers
        Customer::create([
            'code' => 'CUST-001',
            'name' => 'PT Sentosa Motors',
            'phone' => '021-5555-1234',
            'email' => 'service@sentosa.co.id',
            'address' => 'Jl. Gatot Subroto No. 123, Jakarta',
            'is_active' => true,
        ]);

        Customer::create([
            'code' => 'CUST-002',
            'name' => 'Auto Service Center Jakarta',
            'phone' => '021-5555-5678',
            'email' => 'info@autoservice.co.id',
            'address' => 'Jl. Sudirman No. 456, Jakarta',
            'is_active' => true,
        ]);

        Customer::create([
            'code' => 'CUST-003',
            'name' => 'PT Maju Jaya Workshop',
            'phone' => '021-5555-9999',
            'email' => 'workshop@majujaya.co.id',
            'address' => 'Jl. Iskandar Muda No. 789, Jakarta',
            'is_active' => true,
        ]);

        // Create Suppliers
        Supplier::create([
            'name' => 'PT Automotive Supplies Indonesia',
            'contact_person' => 'Budi Santoso',
            'email' => 'sales@autosupply.co.id',
            'phone' => '021-6666-1111',
            'address' => 'Jl. Hayam Wuruk No. 111, Jakarta',
            'notes' => 'Main supplier for paint and primers',
        ]);

        Supplier::create([
            'name' => 'CV Supplies Jaya',
            'contact_person' => 'Siti Nurhaliza',
            'email' => 'order@suppliesjaya.co.id',
            'phone' => '021-6666-2222',
            'address' => 'Jl. Proklamasi No. 222, Jakarta',
            'notes' => 'Supplier for sandpaper and consumables',
        ]);

        Supplier::create([
            'name' => 'PT Indo Parts Trading',
            'contact_person' => 'Ahmad Wijaya',
            'email' => 'procurement@indoparts.co.id',
            'phone' => '021-6666-3333',
            'address' => 'Jl. M.H. Thamrin No. 333, Jakarta',
            'notes' => 'Supplier for body fillers and resins',
        ]);
    }
}
