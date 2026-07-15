<?php

namespace Database\Seeders;

use App\Models\UOM;
use App\Models\UOMConversion;
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
