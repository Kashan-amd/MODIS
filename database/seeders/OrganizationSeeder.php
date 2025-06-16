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
            'OSC',
            'Herzberg Technologies (Pvt) ltd',
            'Amaya Lahore',
            'Amaya Burban',
            'Event Pro (Pvt) ltd',
            'IHC'
        ];

        foreach ($organizations as $org)
        {
            Organization::factory()->create([
                'name' => $org,
            ]);
        }
    }
}
