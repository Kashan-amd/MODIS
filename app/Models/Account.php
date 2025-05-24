<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Akaunting\Money\Money;

class Account extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'account_number',
        'name',
        'type',
        'description',
        'is_active',
        'current_balance',
        'opening_balance',
        'balance_date',
        'organization_id',
        'parent_id',
        'level',
        'is_parent',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_parent' => 'boolean',
        'current_balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'balance_date' => 'date',
        'level' => 'integer',
    ];

    // Account types
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    public static function getTypes(): array
    {
        return [
            self::TYPE_ASSET => 'Asset',
            self::TYPE_LIABILITY => 'Liability',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_INCOME => 'Income',
            self::TYPE_EXPENSE => 'Expense',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function formatMoney($amount): string
    {
        return Money::PKR($amount * 100)->format();
    }

    public function getFormattedCurrentBalanceAttribute(): string
    {
        return $this->formatMoney($this->current_balance);
    }

    public function getFormattedOpeningBalanceAttribute(): string
    {
        return $this->formatMoney($this->opening_balance);
    }

    /**
     * Get the parent account
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Check if account has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if account is a head parent (no organization, accessible across all orgs)
     */
    public function isHeadParent(): bool
    {
        return $this->is_parent && $this->organization_id === null;
    }

    /**
     * Check if account is a main/parent account
     */
    public function isMainAccount(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get the formatted account name with number
     */
    public function getFullAccountNameAttribute(): string
    {
        return "{$this->account_number} - {$this->name}";
    }

    /**
     * Format the account number properly
     */
    public function getFormattedAccountNumberAttribute(): string
    {
        // For parent accounts, just return the original number
        if ($this->is_parent)
        {
            return $this->account_number;
        }

        // For non-parent accounts, extract parent number and suffix if exists
        $parts = explode('-', $this->account_number);
        if (count($parts) === 2)
        {
            return $this->account_number; // Already formatted correctly
        }

        // For non-formatted account numbers, append -1 to indicate it's a sub-account
        return $this->account_number . '-1';
    }

    /**
     * Get all main accounts (parents only)
     *
     * @param int|null $organizationId The organization ID or null to get head parent accounts
     * @param bool $includeHeadParents Whether to include head parent accounts (accessible across all orgs)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getMainAccounts($organizationId, bool $includeHeadParents = false)
    {
        $query = self::whereNull('parent_id');

        if ($includeHeadParents)
        {
            // If we want to include head parents, get organization-specific accounts AND head parents
            $query->where(function ($q) use ($organizationId)
            {
                $q->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            });
        }
        else
        {
            // Otherwise, just get accounts for the specified organization
            $query->where('organization_id', $organizationId);
        }

        return $query->orderBy('account_number')->get();
    }

    /**
     * Create a new head parent account
     *
     * @param array $attributes The account attributes
     * @return \App\Models\Account
     */
    public static function createHeadParent(array $attributes): self
    {
        $attributes['is_parent'] = true;
        $attributes['organization_id'] = null;
        $attributes['parent_id'] = null;

        return self::create($attributes);
    }

    /**
     * Get all accounts in hierarchical form
     *
     * @param int $organizationId The organization ID
     * @param bool $includeHeadParents Whether to include head parent accounts (accessible across all orgs)
     * @return array
     */
    public static function getAccountsHierarchy($organizationId, bool $includeHeadParents = true)
    {
        $mainAccounts = self::getMainAccounts($organizationId, $includeHeadParents);
        $hierarchy = [];

        foreach ($mainAccounts as $account)
        {
            $hierarchy[] = [
                'account' => $account,
                'children' => $account->children()
                    ->orderBy('account_number')
                    ->get()
            ];
        }

        return $hierarchy;
    }
}
