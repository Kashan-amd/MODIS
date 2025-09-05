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
            "name" => "Admin",
            "email" => "admin@example.com",
            "password" => bcrypt("password"), // Ensure to use a secure password
        ]);

        // Seed organizations, accounting data, and items
        $this->call([OrganizationSeeder::class, ItemsSeeder::class, ChartOfAccountsSeeder::class]);
    }
}
