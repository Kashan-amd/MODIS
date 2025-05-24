<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'Animations Expense',
                'category' => 'Visual Effects',
                'description' => 'Costs associated with creating animations for promotional materials or event presentations.',
            ],
            [
                'name' => 'Invitation Cards & Lanyards',
                'category' => 'Event Materials',
                'description' => 'Expenses for designing and printing invitation cards and lanyards for attendees.',
            ],
            [
                'name' => 'Decor Expenses',
                'category' => 'Event Setup',
                'description' => 'Costs related to the decoration of the venue, including themes, props, and aesthetics.',
            ],
            [
                'name' => 'Specialized Lighting',
                'category' => 'Technical Equipment',
                'description' => 'Expenses for unique lighting setups that enhance the ambiance of the event.',
            ],
            [
                'name' => 'General Lighting',
                'category' => 'Technical Equipment',
                'description' => 'Costs for standard lighting solutions to ensure proper visibility at the event.',
            ],
            [
                'name' => 'Generators & Allied Expenses',
                'category' => 'Power Supply',
                'description' => 'Expenses for generators and related equipment to provide electricity for the event.',
            ],
            [
                'name' => 'Valet Expenses',
                'category' => 'Guest Services',
                'description' => 'Costs associated with valet parking services for guests attending the event.',
            ],
            [
                'name' => 'Ushers Expenses',
                'category' => 'Staffing',
                'description' => 'Payments for ushers who assist guests with seating and provide information during the event.',
            ],
            [
                'name' => 'Stage & Platforming Expenses',
                'category' => 'Infrastructure',
                'description' => 'Costs for constructing stages and platforms for performances or presentations.',
            ],
            [
                'name' => 'Labour & Allied Expenses',
                'category' => 'Staffing',
                'description' => 'Payments for labor involved in setting up and managing the event.',
            ],
            [
                'name' => 'Flowers',
                'category' => 'Decor',
                'description' => 'Expenses for floral arrangements used for decoration throughout the venue.',
            ],
            [
                'name' => 'Professional Photography Expenses',
                'category' => 'Media',
                'description' => 'Costs for hiring professional photographers to capture moments during the event.',
            ],
            [
                'name' => 'Branding Expenses',
                'category' => 'Marketing',
                'description' => 'Costs related to branding materials, including banners, signage, and promotional items.',
            ],
            [
                'name' => 'Catering & Allied Expenses',
                'category' => 'Food & Beverage',
                'description' => 'Expenses for food and beverage services provided during the event.',
            ],
            [
                'name' => 'Artist Fee',
                'category' => 'Entertainment',
                'description' => 'Payments made to artists or performers for their participation in the event.',
            ],
            [
                'name' => 'Artist Travel & Allied Charges',
                'category' => 'Travel',
                'description' => 'Costs associated with travel and accommodations for artists participating in the event.',
            ],
            [
                'name' => 'Staff Food, Travel & Stay',
                'category' => 'Staffing',
                'description' => 'Expenses for food, travel, and accommodation for staff working at the event.',
            ],
            [
                'name' => 'SMD Charges',
                'category' => 'Technical Equipment',
                'description' => 'Costs related to the use of Surface-Mount Device (SMD) technology for displays or lighting.',
            ],
            [
                'name' => 'Marquee And Canopy Expense',
                'category' => 'Infrastructure',
                'description' => 'Expenses for renting or setting up marquees and canopies for outdoor events.',
            ],
            [
                'name' => 'Miscellaneous Expenses (Direct)',
                'category' => 'General',
                'description' => 'Unforeseen or miscellaneous costs directly related to the event.',
            ],
            [
                'name' => 'Entertainment',
                'category' => 'Activities',
                'description' => 'Costs for various entertainment options provided during the event, such as performances or activities.',
            ],
            [
                'name' => 'Travelling & Conveyance',
                'category' => 'Travel',
                'description' => 'Expenses for transportation of staff, equipment, or guests to and from the event.',
            ],
            [
                'name' => 'Director Travel & Allied Charges',
                'category' => 'Travel',
                'description' => 'Costs associated with travel and accommodations for directors involved in the event.',
            ],
            [
                'name' => 'Bouncers Expense',
                'category' => 'Security',
                'description' => 'Payments for security personnel to ensure safety and manage crowd control at the event.',
            ],
            [
                'name' => 'Staff Execution Expense',
                'category' => 'Staffing',
                'description' => 'Costs related to the execution of tasks by staff during the event.',
            ],
            [
                'name' => 'Carpet Expense',
                'category' => 'Decor',
                'description' => 'Expenses for renting or purchasing carpets for the event venue.',
            ],
            [
                'name' => 'Sound System/Trussing/DJ',
                'category' => 'Technical Equipment',
                'description' => 'Costs for sound systems, trussing, and DJ services for audio management during the event.',
            ],
            [
                'name' => 'Project Items Rental',
                'category' => 'Equipment Rental',
                'description' => 'Expenses for renting various items needed for the event, such as projectors or screens.',
            ],
            [
                'name' => 'HR / Services Cost',
                'category' => 'Staffing',
                'description' => 'Costs associated with human resources services for staffing and management of the event.',
            ],
            [
                'name' => 'Technical & Consultancy Services',
                'category' => 'Professional Services',
                'description' => 'Costs for hiring technical experts or consultants to provide guidance and support for the event.',
            ],
            [
                'name' => 'Kids Play',
                'category' => 'Entertainment',
                'description' => 'Expenses for organizing activities and play areas for children attending the event.',
            ],
            [
                'name' => 'Tips & Execution on Site',
                'category' => 'Staffing',
                'description' => 'Gratuities and additional payments for staff executing tasks on-site during the event.',
            ],
            [
                'name' => 'Miscellaneous On Site',
                'category' => 'General',
                'description' => 'Unforeseen or miscellaneous costs incurred on-site during the event that do not fit into other categories.',
            ],
        ];

        foreach ($items as $item)
        {
            Item::create($item);
        }
    }
}
