<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sampleUsers = [
            [
                'name' => 'John Smith',
                'username' => 'johnsmith',
                'email' => 'john.smith@example.com',
                'password' => Hash::make('SecurePass123!'),
                'role' => 'user',
                'department' => 'Technical and Networking Department',
                'status' => 'active'
            ],
            [
                'name' => 'Sarah Johnson',
                'username' => 'sarahj',
                'email' => 'sarah.johnson@example.com',
                'password' => Hash::make('SecurePass123!'),
                'role' => 'team_leader',
                'department' => 'Business Dev and Operations',
                'status' => 'active'
            ],
            [
                'name' => 'Michael Brown',
                'username' => 'michaelb',
                'email' => 'michael.brown@example.com',
                'password' => Hash::make('SecurePass123!'),
                'role' => 'user',
                'department' => 'Finance and Accounts',
                'status' => 'inactive'
            ],
            [
                'name' => 'Emily Davis',
                'username' => 'emilyd',
                'email' => 'emily.davis@example.com',
                'password' => Hash::make('SecurePass123!'),
                'role' => 'user',
                'department' => 'Implementation and Support',
                'status' => 'active'
            ]
        ];

        foreach ($sampleUsers as $userData) {
            // Check if user already exists
            if (!User::where('email', $userData['email'])->exists()) {
                User::create($userData);
                $this->command->info("✅ Created user: {$userData['name']}");
            } else {
                $this->command->info("⚠️ User already exists: {$userData['name']}");
            }
        }
    }
}