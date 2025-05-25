<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Standard Chart of Accounts for a service business
        $this->createAssetAccounts();
        $this->createLiabilityAccounts();
        $this->createEquityAccounts();
        $this->createIncomeAccounts();
        $this->createExpenseAccounts();
    }

    protected function createAssetAccounts()
    {
        // Asset accounts (1000-1999)
        $assetAccounts = [
            ['account_number' => '1000', 'name' => 'Cash', 'description' => 'Cash on hand'],
            ['account_number' => '1010', 'name' => 'Petty Cash', 'description' => 'Small cash on hand for minor expenses'],
            ['account_number' => '1100', 'name' => 'Bank Account', 'description' => 'Main checking account'],
            ['account_number' => '1200', 'name' => 'Accounts Receivable', 'description' => 'Money owed by customers'],
            ['account_number' => '1300', 'name' => 'Inventory', 'description' => 'Items held for sale'],
            ['account_number' => '1400', 'name' => 'Prepaid Expenses', 'description' => 'Expenses paid in advance'],
            ['account_number' => '1500', 'name' => 'Equipment', 'description' => 'Office equipment'],
            ['account_number' => '1510', 'name' => 'Accumulated Depreciation - Equipment', 'description' => 'Accumulated depreciation of equipment'],
            ['account_number' => '1600', 'name' => 'Furniture and Fixtures', 'description' => 'Office furniture'],
            ['account_number' => '1610', 'name' => 'Accumulated Depreciation - Furniture', 'description' => 'Accumulated depreciation of furniture'],
        ];

        $this->createAccounts($assetAccounts, 'asset');
    }

    protected function createLiabilityAccounts()
    {
        // Liability accounts (2000-2999)
        $liabilityAccounts = [
            ['account_number' => '2000', 'name' => 'Accounts Payable', 'description' => 'Money owed to vendors'],
            ['account_number' => '2100', 'name' => 'Accrued Expenses', 'description' => 'Expenses incurred but not yet paid'],
            ['account_number' => '2200', 'name' => 'Wages Payable', 'description' => 'Wages owed to employees'],
            ['account_number' => '2300', 'name' => 'Sales Tax Payable', 'description' => 'Sales tax collected but not yet remitted'],
            ['account_number' => '2400', 'name' => 'Income Tax Payable', 'description' => 'Income tax owed but not yet paid'],
            ['account_number' => '2500', 'name' => 'Short-term Loans', 'description' => 'Loans due within one year'],
            ['account_number' => '2600', 'name' => 'Long-term Loans', 'description' => 'Loans due after one year'],
        ];

        $this->createAccounts($liabilityAccounts, 'liability');
    }

    protected function createEquityAccounts()
    {
        // Equity accounts (3000-3999)
        $equityAccounts = [
            ['account_number' => '3000', 'name' => 'Owner\'s Capital', 'description' => 'Owner\'s investment in the business'],
            ['account_number' => '3100', 'name' => 'Owner\'s Drawings', 'description' => 'Owner\'s withdrawals from the business'],
            ['account_number' => '3200', 'name' => 'Retained Earnings', 'description' => 'Accumulated earnings retained in the business'],
        ];

        $this->createAccounts($equityAccounts, 'equity');
    }

    protected function createIncomeAccounts()
    {
        // Income accounts (4000-4999)
        $incomeAccounts = [
            ['account_number' => '4000', 'name' => 'Service Revenue', 'description' => 'Income from services provided'],
            ['account_number' => '4100', 'name' => 'Product Revenue', 'description' => 'Income from product sales'],
            ['account_number' => '4200', 'name' => 'Consulting Revenue', 'description' => 'Income from consulting services'],
            ['account_number' => '4300', 'name' => 'Maintenance Revenue', 'description' => 'Income from maintenance contracts'],
            ['account_number' => '4400', 'name' => 'Interest Income', 'description' => 'Income from interest earned'],
            ['account_number' => '4900', 'name' => 'Miscellaneous Income', 'description' => 'Other income'],
        ];

        $this->createAccounts($incomeAccounts, 'income');
    }

    protected function createExpenseAccounts()
    {
        // Expense accounts (5000-9999)
        $expenseAccounts = [
            ['account_number' => '5000', 'name' => 'Cost of Goods Sold', 'description' => 'Direct costs of goods sold'],
            ['account_number' => '5100', 'name' => 'Direct Labor', 'description' => 'Labor costs directly related to services or products'],
            ['account_number' => '6000', 'name' => 'Salaries and Wages', 'description' => 'Employee salaries and wages'],
            ['account_number' => '6100', 'name' => 'Rent Expense', 'description' => 'Office or building rent'],
            ['account_number' => '6200', 'name' => 'Utilities Expense', 'description' => 'Electricity, water, gas, internet, etc.'],
            ['account_number' => '6300', 'name' => 'Office Supplies', 'description' => 'Office supplies and stationery'],
            ['account_number' => '6400', 'name' => 'Insurance Expense', 'description' => 'Business insurance'],
            ['account_number' => '6500', 'name' => 'Advertising and Marketing', 'description' => 'Promotion and marketing expenses'],
            ['account_number' => '6600', 'name' => 'Travel Expense', 'description' => 'Business travel expenses'],
            ['account_number' => '6700', 'name' => 'Meals and Entertainment', 'description' => 'Business meals and entertainment'],
            ['account_number' => '6800', 'name' => 'Professional Fees', 'description' => 'Legal, accounting, and other professional services'],
            ['account_number' => '7000', 'name' => 'Depreciation Expense', 'description' => 'Depreciation of assets'],
            ['account_number' => '7100', 'name' => 'Bank Fees', 'description' => 'Bank service charges'],
            ['account_number' => '7200', 'name' => 'Interest Expense', 'description' => 'Interest paid on loans'],
            ['account_number' => '8000', 'name' => 'Income Tax Expense', 'description' => 'Business income taxes'],
            ['account_number' => '9000', 'name' => 'Miscellaneous Expense', 'description' => 'Other expenses'],
        ];

        $this->createAccounts($expenseAccounts, 'expense');
    }

    protected function createAccounts($accounts, $type)
    {
        foreach ($accounts as $accountData)
        {
            $account = Account::firstOrNew([
                'account_number' => $accountData['account_number'],
                'organization_id' => null
            ]);

            if (!$account->exists)
            {
                $account->fill([
                    'name' => $accountData['name'],
                    'type' => $type,
                    'description' => $accountData['description'],
                    'is_active' => true,
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'balance_date' => now(),
                    'organization_id' => null,
                ]);

                $account->save();
            }
        }
    }
}
