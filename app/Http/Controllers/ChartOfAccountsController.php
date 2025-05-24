<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChartOfAccountsController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request)
    {
        $organizationId = $request->query('organization_id', null);
        $organizations = Organization::all();

        if (!$organizationId && $organizations->count() > 0)
        {
            $organizationId = $organizations->first()->id;
        }

        $accountsHierarchy = Account::getAccountsHierarchy($organizationId);

        return view('accounting.chart-of-accounts.index', [
            'accountsHierarchy' => $accountsHierarchy,
            'organizations' => $organizations,
            'selectedOrganizationId' => $organizationId,
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(Request $request)
    {
        $organizations = Organization::all();
        $organizationId = $request->query('organization_id', ($organizations->count() > 0 ? $organizations->first()->id : null));

        // Get parent accounts for dropdown
        $parentAccounts = Account::where('organization_id', $organizationId)
            ->whereNull('parent_id')
            ->orderBy('account_number')
            ->get();

        return view('accounting.chart-of-accounts.create', [
            'organizations' => $organizations,
            'parentAccounts' => $parentAccounts,
            'accountTypes' => Account::getTypes(),
            'selectedOrganizationId' => $organizationId,
        ]);
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts')->where(function ($query) use ($request)
                {
                    return $query->where('organization_id', $request->organization_id);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(Account::getTypes())),
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'balance_date' => 'nullable|date',
            'organization_id' => 'required|exists:organizations,id',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        // Determine level based on parent
        $level = 0;
        $isParent = false;

        if ($request->parent_id)
        {
            $parentAccount = Account::find($request->parent_id);
            $level = $parentAccount->level + 1;

            // Update parent account's is_parent flag
            $parentAccount->update(['is_parent' => true]);
        }

        // Create the account
        $account = Account::create(array_merge($validated, [
            'level' => $level,
            'is_parent' => $isParent,
            'is_active' => $request->has('is_active'),
        ]));

        return redirect()->route('accounting.chart-of-accounts.index', ['organization_id' => $request->organization_id])
            ->with('success', 'Account created successfully.');
    }

    /**
     * Show the form for editing the account.
     */
    public function edit(Account $account)
    {
        $organizations = Organization::all();

        // Get parent accounts for dropdown, excluding this account and its children
        $parentAccounts = Account::where('organization_id', $account->organization_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $account->id)
            ->orderBy('account_number')
            ->get();

        return view('accounting.chart-of-accounts.edit', [
            'account' => $account,
            'organizations' => $organizations,
            'parentAccounts' => $parentAccounts,
            'accountTypes' => Account::getTypes(),
        ]);
    }

    /**
     * Update the account.
     */
    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'account_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('chart_of_accounts')->where(function ($query) use ($request, $account)
                {
                    return $query->where('organization_id', $request->organization_id)
                        ->where('id', '!=', $account->id);
                })
            ],
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(Account::getTypes())),
            'description' => 'nullable|string',
            'opening_balance' => 'nullable|numeric',
            'balance_date' => 'nullable|date',
            'organization_id' => 'required|exists:organizations,id',
            'parent_id' => [
                'nullable',
                'exists:chart_of_accounts,id',
                function ($attribute, $value, $fail) use ($account)
                {
                    // Prevent setting a child as parent of its parent
                    if ($value == $account->id)
                    {
                        $fail('An account cannot be its own parent.');
                    }

                    // Check if selected parent is not one of the account's children
                    if ($value)
                    {
                        $childrenIds = $account->children()->pluck('id')->toArray();
                        if (in_array($value, $childrenIds))
                        {
                            $fail('Cannot set a child account as parent.');
                        }
                    }
                },
            ],
            'is_active' => 'boolean',
        ]);

        // Determine level based on parent
        $level = 0;

        if ($request->parent_id)
        {
            $parentAccount = Account::find($request->parent_id);
            $level = $parentAccount->level + 1;

            // Update parent account's is_parent flag
            $parentAccount->update(['is_parent' => true]);
        }

        // If parent_id is being removed, update the old parent's is_parent flag if needed
        if ($account->parent_id && $account->parent_id != $request->parent_id)
        {
            $oldParent = Account::find($account->parent_id);
            if ($oldParent && $oldParent->children()->count() <= 1)
            {
                $oldParent->update(['is_parent' => false]);
            }
        }

        // Update the account
        $account->update(array_merge($validated, [
            'level' => $level,
            'is_active' => $request->has('is_active'),
        ]));

        return redirect()->route('accounting.chart-of-accounts.index', ['organization_id' => $request->organization_id])
            ->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the account.
     */
    public function destroy(Account $account)
    {
        $organizationId = $account->organization_id;

        // Check if account has transactions
        if ($account->transactions()->exists())
        {
            return redirect()->back()->with('error', 'Cannot delete account with associated transactions.');
        }

        // Update parent's is_parent flag if needed
        if ($account->parent_id)
        {
            $parent = Account::find($account->parent_id);
            if ($parent && $parent->children()->count() <= 1)
            {
                $parent->update(['is_parent' => false]);
            }
        }

        $account->delete();

        return redirect()->route('accounting.chart-of-accounts.index', ['organization_id' => $organizationId])
            ->with('success', 'Account deleted successfully.');
    }
}
