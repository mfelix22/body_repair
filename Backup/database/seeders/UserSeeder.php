<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@bodyrepair.com',
                'password' => 'password',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Field Admin',
                'username' => 'admin',
                'email' => 'admin@bodyrepair.com',
                'password' => 'password',
                'role' => 'admin',
            ],
            [
                'name' => 'Purchasing Staff',
                'username' => 'purchasing',
                'email' => 'purchasing@bodyrepair.com',
                'password' => 'password',
                'role' => 'purchasing',
            ],
            [
                'name' => 'Field Staff',
                'username' => 'staffwarehouse',
                'email' => 'staff.warehouse@bodyrepair.com',
                'password' => 'password',
                'role' => 'staff|warehouse',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])
                ->orWhere('username', $userData['username'])
                ->first();

            if ($user) {
                $user->update([
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                ]);
                continue;
            }

            User::create([
                'name' => $userData['name'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'role' => $userData['role'],
            ]);
        }
    }
}
