<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
        ]);

        // create branchs row
        \App\Models\Branch::create([
            'name' => 'Head Office',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
        ]);

        // create employees row
        \App\Models\Employee::create([
            'nip' => '1',
            'user_id' => 1,
            'name' => 'John Doe',
            'branch_id' => 1,
        ]);
    }
}
