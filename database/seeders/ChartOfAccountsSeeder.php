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
        // Asset accounts (01-19)
        $assetAccounts = [
            ['account_number' => '01', 'name' => 'Cash', 'description' => 'Cash on hand'],
            ['account_number' => '02', 'name' => 'Petty Cash', 'description' => 'Small cash on hand for minor expenses'],
            ['account_number' => '03', 'name' => 'Bank Account', 'description' => 'Main checking account'],
            ['account_number' => '04', 'name' => 'Accounts Receivable', 'description' => 'Money owed by customers'],
            ['account_number' => '05', 'name' => 'Inventory', 'description' => 'Items held for sale'],
            ['account_number' => '06', 'name' => 'Prepaid Expenses', 'description' => 'Expenses paid in advance'],
            ['account_number' => '07', 'name' => 'Equipment', 'description' => 'Office equipment'],
            ['account_number' => '08', 'name' => 'Accumulated Depreciation - Equipment', 'description' => 'Accumulated depreciation of equipment'],
            ['account_number' => '09', 'name' => 'Furniture and Fixtures', 'description' => 'Office furniture'],
            ['account_number' => '10', 'name' => 'Accumulated Depreciation - Furniture', 'description' => 'Accumulated depreciation of furniture'],
        ];

        $this->createAccounts($assetAccounts, 'asset');
    }

    protected function createLiabilityAccounts()
    {
        // Liability accounts (20-39)
        $liabilityAccounts = [
            ['account_number' => '20', 'name' => 'Accounts Payable', 'description' => 'Money owed to vendors'],
            ['account_number' => '21', 'name' => 'Accrued Expenses', 'description' => 'Expenses incurred but not yet paid'],
            ['account_number' => '22', 'name' => 'Wages Payable', 'description' => 'Wages owed to employees'],
            ['account_number' => '23', 'name' => 'Sales Tax Payable', 'description' => 'Sales tax collected but not yet remitted'],
            ['account_number' => '24', 'name' => 'Income Tax Payable', 'description' => 'Income tax owed but not yet paid'],
            ['account_number' => '25', 'name' => 'Short-term Loans', 'description' => 'Loans due within one year'],
            ['account_number' => '26', 'name' => 'Long-term Loans', 'description' => 'Loans due after one year'],
        ];

        $this->createAccounts($liabilityAccounts, 'liability');
    }

    protected function createEquityAccounts()
    {
        // Equity accounts (40-59)
        $equityAccounts = [
            ['account_number' => '40', 'name' => 'Owner\'s Capital', 'description' => 'Owner\'s investment in the business'],
            ['account_number' => '41', 'name' => 'Owner\'s Drawings', 'description' => 'Owner\'s withdrawals from the business'],
            ['account_number' => '42', 'name' => 'Retained Earnings', 'description' => 'Accumulated earnings retained in the business'],
        ];

        $this->createAccounts($equityAccounts, 'equity');
    }

    protected function createIncomeAccounts()
    {
        // Income accounts (60-79)
        $incomeAccounts = [
            ['account_number' => '60', 'name' => 'Service Revenue', 'description' => 'Income from services provided'],
            ['account_number' => '61', 'name' => 'Product Revenue', 'description' => 'Income from product sales'],
            ['account_number' => '62', 'name' => 'Consulting Revenue', 'description' => 'Income from consulting services'],
            ['account_number' => '63', 'name' => 'Maintenance Revenue', 'description' => 'Income from maintenance contracts'],
            ['account_number' => '64', 'name' => 'Interest Income', 'description' => 'Income from interest earned'],
            ['account_number' => '65', 'name' => 'Miscellaneous Income', 'description' => 'Other income'],
        ];

        $this->createAccounts($incomeAccounts, 'income');
    }

    protected function createExpenseAccounts()
    {
        // Expense accounts (80-100)
        $expenseAccounts = [
            ['account_number' => '80', 'name' => 'Cost of Goods Sold', 'description' => 'Direct costs of goods sold'],
            ['account_number' => '81', 'name' => 'Direct Labor', 'description' => 'Labor costs directly related to services or products'],
            ['account_number' => '82', 'name' => 'Salaries and Wages', 'description' => 'Employee salaries and wages'],
            ['account_number' => '83', 'name' => 'Rent Expense', 'description' => 'Office or building rent'],
            ['account_number' => '84', 'name' => 'Utilities Expense', 'description' => 'Electricity, water, gas, internet, etc.'],
            ['account_number' => '85', 'name' => 'Office Supplies', 'description' => 'Office supplies and stationery'],
            ['account_number' => '86', 'name' => 'Insurance Expense', 'description' => 'Business insurance'],
            ['account_number' => '87', 'name' => 'Advertising and Marketing', 'description' => 'Promotion and marketing expenses'],
            ['account_number' => '88', 'name' => 'Travel Expense', 'description' => 'Business travel expenses'],
            ['account_number' => '89', 'name' => 'Meals and Entertainment', 'description' => 'Business meals and entertainment'],
            ['account_number' => '90', 'name' => 'Professional Fees', 'description' => 'Legal, accounting, and other professional services'],
            ['account_number' => '91', 'name' => 'Depreciation Expense', 'description' => 'Depreciation of assets'],
            ['account_number' => '92', 'name' => 'Bank Fees', 'description' => 'Bank service charges'],
            ['account_number' => '93', 'name' => 'Interest Expense', 'description' => 'Interest paid on loans'],
            ['account_number' => '94', 'name' => 'Income Tax Expense', 'description' => 'Business income taxes'],
            ['account_number' => '95', 'name' => 'Miscellaneous Expense', 'description' => 'Other expenses'],
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
                    'debit_balance' => 0.00,
                    'credit_balance' => 0.00,
                    'current_balance' => 0.00,
                    'balance_date' => now(),
                    'parent_id' => null,
                    'level' => 0,
                    'is_parent' => true,
                ]);

                $account->save();
            }
        }
    }
}
