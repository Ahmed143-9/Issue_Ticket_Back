<?php
// database/seeders/AdminUserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if admin already exists
        if (!User::where('username', 'Admin')->exists()) {
            User::create([
                'name' => 'Admin User',
                'username' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'department' => 'Management',
                'status' => 'active'
            ]);
            $this->command->info('✅ Default admin user created!');
        } else {
            // Admin already exists, update password
            $admin = User::where('username', 'Admin')->first();
            $admin->update([
                'password' => Hash::make('Admin@123'),
                'status' => 'active'
            ]);
            $this->command->info('✅ Admin user password updated!');
        }
    }
}