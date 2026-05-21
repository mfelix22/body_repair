<?php

namespace Database\Seeders;

use App\Models\UOM;
use App\Models\UOMConversion;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BodyRepairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default user
        $user = User::updateOrCreate(
            ['email' => 'admin@bodyrepair.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create UOMs
        $piece = UOM::updateOrCreate(
            ['code' => 'PCS'],
            ['name' => 'Piece', 'description' => 'Individual pieces']
        );
        $box = UOM::updateOrCreate(
            ['code' => 'BOX'],
            ['name' => 'Box', 'description' => 'Box packaging']
        );
        $liter = UOM::updateOrCreate(
            ['code' => 'LTR'],
            ['name' => 'Liter', 'description' => 'Liquid volume']
        );
        $ml = UOM::updateOrCreate(
            ['code' => 'ML'],
            ['name' => 'Milliliter', 'description' => 'Small liquid volume']
        );
        $kg = UOM::updateOrCreate(
            ['code' => 'KG'],
            ['name' => 'Kilogram', 'description' => 'Weight']
        );
        $gram = UOM::updateOrCreate(
            ['code' => 'GRM'],
            ['name' => 'Gram', 'description' => 'Small weight']
        );

        // Create UOM Conversions
        UOMConversion::updateOrCreate(
            ['from_uom_id' => $box->id, 'to_uom_id' => $piece->id],
            ['conversion_factor' => 10]
        );
        UOMConversion::updateOrCreate(
            ['from_uom_id' => $liter->id, 'to_uom_id' => $ml->id],
            ['conversion_factor' => 1000]
        );
        UOMConversion::updateOrCreate(
            ['from_uom_id' => $kg->id, 'to_uom_id' => $gram->id],
            ['conversion_factor' => 1000]
        );

        // Create Items
        // Paint - stored in ML (smallest)
        $paint = Item::updateOrCreate(
            ['code' => 'PAINT-001'],
            [
                'name' => 'Automotive Paint - Red',
                'description' => 'Premium automotive paint, red color',
                'category' => 'Paint',
                'smallest_uom_id' => $ml->id,
                'reorder_level' => 5000, // 5 liters
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $paint->id, 'uom_id' => $ml->id],
            [
                'conversion_to_smallest' => 1,
                'price' => 0.05, // $0.05 per ML
                'is_default' => false,
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $paint->id, 'uom_id' => $liter->id],
            [
                'conversion_to_smallest' => 1000,
                'price' => 50, // $50 per liter
                'is_default' => true,
            ]
        );

        // Sandpaper - stored in PCS (smallest)
        $sandpaper = Item::updateOrCreate(
            ['code' => 'SAND-120'],
            [
                'name' => 'Sandpaper 120 Grit',
                'description' => 'Medium grit sandpaper for body work',
                'category' => 'Consumables',
                'smallest_uom_id' => $piece->id,
                'reorder_level' => 50,
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $sandpaper->id, 'uom_id' => $piece->id],
            [
                'conversion_to_smallest' => 1,
                'price' => 2, // $2 per piece
                'is_default' => false,
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $sandpaper->id, 'uom_id' => $box->id],
            [
                'conversion_to_smallest' => 10,
                'price' => 18, // $18 per box (10% discount)
                'is_default' => true,
            ]
        );

        // Body Filler - stored in GRAM (smallest)
        $filler = Item::updateOrCreate(
            ['code' => 'FILL-001'],
            [
                'name' => 'Body Filler Compound',
                'description' => 'Professional grade body filler',
                'category' => 'Body Work',
                'smallest_uom_id' => $gram->id,
                'reorder_level' => 10000, // 10 kg
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $filler->id, 'uom_id' => $gram->id],
            [
                'conversion_to_smallest' => 1,
                'price' => 0.02, // $0.02 per gram
                'is_default' => false,
            ]
        );

        ItemUOM::updateOrCreate(
            ['item_id' => $filler->id, 'uom_id' => $kg->id],
            [
                'conversion_to_smallest' => 1000,
                'price' => 20, // $20 per kg
                'is_default' => true,
            ]
        );

        // Create initial stock
        Stock::updateOrCreate(
            ['item_id' => $paint->id, 'location' => 'default'],
            ['quantity' => 10000] // 10 liters in ML
        );

        Stock::updateOrCreate(
            ['item_id' => $sandpaper->id, 'location' => 'default'],
            ['quantity' => 200] // 200 pieces
        );

        Stock::updateOrCreate(
            ['item_id' => $filler->id, 'location' => 'default'],
            ['quantity' => 25000] // 25 kg in grams
        );

        // Create sample customers
        Customer::updateOrCreate(
            ['code' => 'CUST-001'],
            [
                'name' => 'John Doe',
                'phone' => '555-0101',
                'email' => 'john@example.com',
                'address' => '123 Main St, City',
            ]
        );

        Customer::updateOrCreate(
            ['code' => 'CUST-002'],
            [
                'name' => 'ABC Auto Shop',
                'phone' => '555-0102',
                'email' => 'info@abcauto.com',
                'address' => '456 Market St, City',
            ]
        );

        $this->command->info('Body Repair System seeded successfully!');
    }
}
