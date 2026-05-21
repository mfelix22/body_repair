<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PRWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Create test users with different roles if they don't exist
        $staff = User::firstOrCreate(
            ['username' => 'staff_user'],
            [
                'name' => 'Budi Hartanto',
                'email' => 'budi@company.com',
                'password' => bcrypt('password'),
                'role' => 'staff',
                'signature_path' => 'signatures/staff_signature.png',
            ]
        );

        $manager = User::firstOrCreate(
            ['username' => 'manager_user'],
            [
                'name' => 'Rina Wijaya',
                'email' => 'rina@company.com',
                'password' => bcrypt('password'),
                'role' => 'manager',
                'signature_path' => 'signatures/manager_signature.png',
            ]
        );

        $director = User::firstOrCreate(
            ['username' => 'director_user'],
            [
                'name' => 'Bambang Suryanto',
                'email' => 'bambang@company.com',
                'password' => bcrypt('password'),
                'role' => 'director',
                'signature_path' => 'signatures/director_signature.png',
            ]
        );

        $purchasing = User::firstOrCreate(
            ['username' => 'purchasing_user'],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@company.com',
                'password' => bcrypt('password'),
                'role' => 'purchasing',
                'signature_path' => 'signatures/purchasing_signature.png',
            ]
        );

        // Get or create some items for the PR
        $items = Item::take(2)->get();
        if ($items->isEmpty()) {
            $items = [];
            for ($i = 0; $i < 2; $i++) {
                $items[] = Item::create([
                    'name' => 'Service Item ' . ($i + 1),
                    'code' => 'SVC-' . sprintf('%03d', $i + 1),
                    'type' => 'jasa',
                    'smallest_uom_id' => 1,
                ]);
            }
        }

        // Create a PR that goes through the complete workflow
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-DEMO-' . date('Ymd') . '-001',
            'request_date' => now(),
            'requested_by' => $staff->id,
            'notes' => 'Demo PR for workflow testing - Service request',
            'status' => 'on_progress',
            'type' => 'Jasa',
            'require_acknowledgement' => true,
        ]);

        // Add details to the PR
        foreach ($items->take(1) as $item) {
            PurchaseRequestDetail::create([
                'purchase_request_id' => $pr->id,
                'item_id' => $item->id,
                'uom_id' => $item->smallestUom?->id ?? 1,
                'quantity' => 10,
                'notes' => 'Service ' . $item->name,
            ]);
        }

        // Simulate the complete approval workflow
        // Step 1: Manager approves
        $pr->update([
            'status' => 'dept_head_approved',
            'dept_head_by' => $manager->id,
            'dept_head_at' => now()->subHours(2),
        ]);

        // Step 2: Director approves
        $pr->update([
            'status' => 'gm_approved',
            'gm_by' => $director->id,
            'gm_at' => now()->subHour(1),
        ]);

        // Step 3: Purchasing marks received
        $pr->update([
            'status' => 'completed',
            'purchasing_received_by' => $purchasing->id,
            'purchasing_received_at' => now(),
        ]);

        // Create another PR that is still on_progress for testing
        $pr2 = PurchaseRequest::create([
            'pr_number' => 'PR-DEMO-' . date('Ymd') . '-002',
            'request_date' => now(),
            'requested_by' => $staff->id,
            'notes' => 'Demo PR in progress - Items request',
            'status' => 'on_progress',
            'type' => 'Barang',
            'require_acknowledgement' => false,
        ]);

        // Add items to the second PR
        if ($items->count() > 1) {
            PurchaseRequestDetail::create([
                'purchase_request_id' => $pr2->id,
                'item_id' => $items[1]->id,
                'uom_id' => $items[1]->smallestUom?->id ?? 1,
                'quantity' => 5,
                'notes' => 'Regular items',
            ]);
        }

        $this->command->info('PR Workflow Demo Seeder completed!');
        $this->command->info('');
        $this->command->info('Test Users Created:');
        $this->command->info('- Staff: ' . $staff->username . ' (password: password)');
        $this->command->info('- Manager: ' . $manager->username . ' (password: password)');
        $this->command->info('- Director: ' . $director->username . ' (password: password)');
        $this->command->info('- Purchasing: ' . $purchasing->username . ' (password: password)');
        $this->command->info('');
        $this->command->info('Demo PRs Created:');
        $this->command->info('- PR #1: Status = COMPLETED (Full workflow with all signatures)');
        $this->command->info('- PR #2: Status = ON PROGRESS (Awaiting manager approval)');
    }
}
