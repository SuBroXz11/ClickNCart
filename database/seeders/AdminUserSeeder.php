<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'phone_number' => '1234567890',
            'address' => 'Admin Address',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'verified',
        ]);
    }
}