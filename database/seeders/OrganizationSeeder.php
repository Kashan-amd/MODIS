<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample organizations
        $organizations = [
            'Main Corporation',
            'OSC Finance',
            'Corporate Partners',
            'Funding Solutions',
            'Investment Group'
        ];

        foreach ($organizations as $org)
        {
            Organization::factory()->create([
                'name' => $org,
            ]);
        }
    }
}
