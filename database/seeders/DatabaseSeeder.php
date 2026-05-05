<?php

namespace Database\Seeders;

use App\Models\User;
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
        User::factory()->create([
            'name' => 'Admin User',
            'phone' => '01700000000',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Manager User',
            'phone' => '01700000001',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);

        User::factory()->create([
            'name' => 'Customer User',
            'phone' => '01700000002',
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);
    }
}
