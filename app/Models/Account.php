<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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

    // Account types as constants
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_INCOME = 'income';
    const TYPE_EXPENSE = 'expense';

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function transactionEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    // Static Methods
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

    // Accessors & Mutators
    public function getFullAccountNameAttribute(): string
    {
        return "{$this->account_number} - {$this->name}";
    }

    public function getFormattedCurrentBalanceAttribute(): string
    {
        return Money::PKR($this->current_balance * 100)->format();
    }

    public function getFormattedOpeningBalanceAttribute(): string
    {
        return Money::PKR($this->opening_balance * 100)->format();
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrganizationAccounts($query, $organizationId)
    {
        return $query->where(function ($q) use ($organizationId)
        {
            $q->where('organization_id', $organizationId)
                ->orWhereNull('organization_id');
        });
    }

    // Helper Methods
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function isHeadParent(): bool
    {
        return $this->is_parent && $this->organization_id === null;
    }

    public function isMainAccount(): bool
    {
        return $this->parent_id === null;
    }

    public function transactions()
    {
        return Transaction::whereHas('entries', fn($query) => $query->where('account_id', $this->id));
    }

    public function getNextSubAccountNumber(): string
    {
        // Get all child account numbers
        $subAccounts = $this->children()
            ->where('account_number', 'like', $this->account_number . '-%')
            ->get();

        if ($subAccounts->isEmpty())
        {
            return $this->account_number . '-1';
        }

        // Extract the highest sub-account sequence number
        $maxSeq = $subAccounts
            ->map(function ($account)
            {
                $parts = explode('-', $account->account_number);
                return isset($parts[1]) ? (int)$parts[1] : 0;
            })
            ->max();

        return $this->account_number . '-' . ($maxSeq + 1);
    }

    // Add method to standardize account number format
    public static function standardizeAccountNumber(string $number): string
    {
        // Remove any extra spaces
        $number = trim($number);

        if (str_contains($number, '-'))
        {
            // For sub-accounts, ensure proper formatting
            list($main, $sub) = array_pad(explode('-', $number, 2), 2, '');
            return sprintf('%s-%d', trim($main), (int)$sub);
        }

        // For main accounts, just return the number
        return $number;
    }

    // Creation Methods
    public static function createHeadAccount(array $attributes): self
    {
        if (str_contains($attributes['account_number'], '-'))
        {
            throw new \InvalidArgumentException('Head account number should not contain a hyphen (-)');
        }

        $attributes['level'] = 0;
        $attributes['parent_id'] = null;

        if (!empty($attributes['is_parent']) && empty($attributes['organization_id']))
        {
            $attributes['organization_id'] = null;
        }

        return self::create($attributes);
    }

    public function createSubAccount(array $attributes): self
    {
        $attributes = array_merge($attributes, [
            'is_parent' => false,
            'parent_id' => $this->id,
            'level' => $this->level + 1
        ]);

        if (!str_contains($attributes['account_number'], '-'))
        {
            $attributes['account_number'] = $this->getNextSubAccountNumber();
        }

        $account = self::create($attributes);

        if (!$this->is_parent)
        {
            $this->update(['is_parent' => true]);
        }

        return $account;
    }

    public static function getValidParents($organizationId): Collection
    {
        return self::where('is_parent', true)
            ->organizationAccounts($organizationId)
            ->orderBy('account_number')
            ->get();
    }
}
