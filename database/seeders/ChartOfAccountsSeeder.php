<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first organization or create one if none exists
        $organization = Organization::first();

        if (!$organization)
        {
            $this->command->info('No organization found. Please run OrganizationSeeder first.');
            return;
        }

        // Default chart of accounts
        $accounts = [
            // Asset accounts (1000-1999)
            ['account_number' => '1000', 'name' => 'Cash', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1010', 'name' => 'Bank Account', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1100', 'name' => 'Accounts Receivable', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1200', 'name' => 'Inventory', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1300', 'name' => 'Office Equipment', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1400', 'name' => 'Furniture & Fixtures', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1500', 'name' => 'Vehicles', 'type' => Account::TYPE_ASSET],
            ['account_number' => '1600', 'name' => 'Buildings', 'type' => Account::TYPE_ASSET],

            // Liability accounts (2000-2999)
            ['account_number' => '2000', 'name' => 'Accounts Payable', 'type' => Account::TYPE_LIABILITY],
            ['account_number' => '2100', 'name' => 'Accrued Expenses', 'type' => Account::TYPE_LIABILITY],
            ['account_number' => '2200', 'name' => 'Sales Tax Payable', 'type' => Account::TYPE_LIABILITY],
            ['account_number' => '2300', 'name' => 'Income Tax Payable', 'type' => Account::TYPE_LIABILITY],
            ['account_number' => '2400', 'name' => 'Bank Loans Payable', 'type' => Account::TYPE_LIABILITY],
            ['account_number' => '2500', 'name' => 'Long-term Debt', 'type' => Account::TYPE_LIABILITY],

            // Equity accounts (3000-3999)
            ['account_number' => '3000', 'name' => 'Owner\'s Equity', 'type' => Account::TYPE_EQUITY],
            ['account_number' => '3100', 'name' => 'Retained Earnings', 'type' => Account::TYPE_EQUITY],
            ['account_number' => '3900', 'name' => 'Net Income', 'type' => Account::TYPE_EQUITY],

            // Income accounts (4000-4999)
            ['account_number' => '4000', 'name' => 'Sales Revenue', 'type' => Account::TYPE_INCOME],
            ['account_number' => '4100', 'name' => 'Service Revenue', 'type' => Account::TYPE_INCOME],
            ['account_number' => '4200', 'name' => 'Interest Income', 'type' => Account::TYPE_INCOME],
            ['account_number' => '4300', 'name' => 'Rental Income', 'type' => Account::TYPE_INCOME],
            ['account_number' => '4400', 'name' => 'Other Income', 'type' => Account::TYPE_INCOME],

            // Expense accounts (5000-9999)
            ['account_number' => '5000', 'name' => 'Cost of Goods Sold', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '5100', 'name' => 'Purchases', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6000', 'name' => 'Advertising Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6100', 'name' => 'Bank Fees', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6200', 'name' => 'Depreciation Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6300', 'name' => 'Insurance Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6400', 'name' => 'Interest Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6500', 'name' => 'Office Supplies', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6600', 'name' => 'Payroll Expenses', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6700', 'name' => 'Rent Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6800', 'name' => 'Repairs & Maintenance', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '6900', 'name' => 'Telephone Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '7000', 'name' => 'Travel Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '7100', 'name' => 'Utilities Expense', 'type' => Account::TYPE_EXPENSE],
            ['account_number' => '9000', 'name' => 'Miscellaneous Expense', 'type' => Account::TYPE_EXPENSE],
        ];

        $today = now()->format('Y-m-d');

        foreach ($accounts as $account)
        {
            Account::create([
                'account_number' => $account['account_number'],
                'name' => $account['name'],
                'type' => $account['type'],
                'description' => 'Standard account for ' . $account['name'],
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
                'balance_date' => $today,
                'organization_id' => $organization->id,
            ]);
        }

        $this->command->info('Chart of Accounts seeded successfully!');
    }
}
