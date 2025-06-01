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

    // Define account levels
    const LEVEL_PARENT = 0;      // Main parent accounts (Assets, Liabilities, etc.)
    const LEVEL_CHILD = 1;       // Sub-accounts under parent
    const LEVEL_GRANDCHILD = 2;  // Sub-accounts under child

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

    public function grandchildren(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')
            ->with('children');
    }

    // Get all descendants (children + grandchildren)
    public function descendants(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')
            ->with('descendants');
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

    // Get account level name
    public function getLevelNameAttribute(): string
    {
        return match ($this->level)
        {
            self::LEVEL_PARENT => 'Parent',
            self::LEVEL_CHILD => 'Child',
            self::LEVEL_GRANDCHILD => 'Grandchild',
            default => 'Unknown'
        };
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

    // Check if account can have children based on level
    public function canHaveChildren(): bool
    {
        return $this->level < self::LEVEL_GRANDCHILD;
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
        if ($this->level === self::LEVEL_PARENT)
        {
            // For parent accounts (10, 11, 12), generate child accounts (1010, 1011, 1012)
            $childAccounts = $this->children()
                ->where('account_number', 'like', $this->account_number . '%')
                ->where('account_number', 'not like', '%-_%')  // Exclude grandchildren
                ->get();

            if ($childAccounts->isEmpty())
            {
                return $this->account_number . '10';  // First child: 10 -> 1010
            }

            // Find the highest child number
            $maxSeq = $childAccounts
                ->map(function ($account)
                {
                    // Extract the last two digits from child accounts (1010 -> 10, 1011 -> 11)
                    $number = $account->account_number;
                    return (int)substr($number, -2);
                })
                ->max();

            return $this->account_number . str_pad($maxSeq + 1, 2, '0', STR_PAD_LEFT);
        }
        elseif ($this->level === self::LEVEL_CHILD)
        {
            // For child accounts (1010, 1011), generate grandchild accounts (1010-1, 1010-2)
            $grandchildAccounts = $this->children()
                ->where('account_number', 'like', $this->account_number . '-%')
                ->get();

            if ($grandchildAccounts->isEmpty())
            {
                return $this->account_number . '-1';  // First grandchild
            }

            // Extract the highest grandchild sequence number
            $maxSeq = $grandchildAccounts
                ->map(function ($account)
                {
                    $parts = explode('-', $account->account_number);
                    return isset($parts[1]) ? (int)$parts[1] : 0;
                })
                ->max();

            return $this->account_number . '-' . ($maxSeq + 1);
        }

        throw new \Exception('Cannot generate sub-account number for this account level');
    }

    // Helper method to get the next available parent account number
    public static function getNextParentAccountNumber($organizationId = null): string
    {
        $query = self::where('level', self::LEVEL_PARENT);

        if ($organizationId)
        {
            $query->organizationAccounts($organizationId);
        }
        else
        {
            $query->whereNull('organization_id');
        }

        $lastParent = $query->orderBy('account_number', 'desc')->first();

        if (!$lastParent)
        {
            return '10'; // Start with 10
        }

        $lastNumber = (int)$lastParent->account_number;
        return (string)($lastNumber + 1);
    }

    // Helper method to check if account number follows the new standard
    public static function isValidAccountNumberFormat(string $accountNumber, int $level): bool
    {
        switch ($level)
        {
            case self::LEVEL_PARENT:
                // Parent: 2 digits (10, 11, 12, etc.)
                return preg_match('/^\d{2}$/', $accountNumber);

            case self::LEVEL_CHILD:
                // Child: 3-4 digits (1010, 1011, 1012, etc.)
                return preg_match('/^\d{3,4}$/', $accountNumber);

            case self::LEVEL_GRANDCHILD:
                // Grandchild: XXXX-X format (1010-1, 1010-2, etc.)
                return preg_match('/^\d{3,4}-\d+$/', $accountNumber);

            default:
                return false;
        }
    }

    // Validation method for sub-account numbers
    private function validateSubAccountNumber(string $accountNumber): void
    {
        if ($this->level === self::LEVEL_PARENT)
        {
            // Child accounts should be 3-4 digits (1010, 1011, 1012, etc.)
            if (!preg_match('/^\d{3,4}$/', $accountNumber))
            {
                throw new \InvalidArgumentException('Child account number should be 3-4 digits (e.g., 1010, 1011)');
            }

            // Should start with parent account number
            if (!str_starts_with($accountNumber, $this->account_number))
            {
                throw new \InvalidArgumentException("Child account number should start with parent number: {$this->account_number}");
            }
        }
        elseif ($this->level === self::LEVEL_CHILD)
        {
            // Grandchild accounts should follow pattern: XXXX-X (e.g., 1010-1, 1010-2)
            if (!preg_match('/^\d{3,4}-\d+$/', $accountNumber))
            {
                throw new \InvalidArgumentException('Grandchild account number should follow pattern XXXX-X (e.g., 1010-1, 1010-2)');
            }

            // Should start with child account number
            if (!str_starts_with($accountNumber, $this->account_number . '-'))
            {
                throw new \InvalidArgumentException("Grandchild account number should start with child number: {$this->account_number}-");
            }
        }
    }

    // Add method to standardize account number format
    public static function standardizeAccountNumber(string $number): string
    {
        // Remove any extra spaces
        $number = trim($number);

        if (str_contains($number, '-'))
        {
            // For grandchild accounts (1010-1, 1010-2), ensure proper formatting
            list($main, $sub) = array_pad(explode('-', $number, 2), 2, '');
            return sprintf('%s-%d', trim($main), (int)$sub);
        }

        // For parent and child accounts, just return the number
        return $number;
    }

    // Creation Methods
    public static function createHeadAccount(array $attributes): self
    {
        if (str_contains($attributes['account_number'], '-'))
        {
            throw new \InvalidArgumentException('Head account number should not contain a hyphen (-)');
        }

        // Validate parent account number format (should be 2 digits: 10, 11, 12, etc.)
        if (!preg_match('/^\d{2}$/', $attributes['account_number']))
        {
            throw new \InvalidArgumentException('Parent account number should be 2 digits (e.g., 10, 11, 12)');
        }

        $attributes['level'] = self::LEVEL_PARENT;
        $attributes['parent_id'] = null;
        $attributes['is_parent'] = true;

        if (!empty($attributes['is_parent']) && empty($attributes['organization_id']))
        {
            $attributes['organization_id'] = null;
        }

        return self::create($attributes);
    }

    public function createSubAccount(array $attributes): self
    {
        if (!$this->canHaveChildren())
        {
            throw new \Exception('This account cannot have children (max level reached)');
        }

        $attributes = array_merge($attributes, [
            'parent_id' => $this->id,
            'level' => $this->level + 1,
            'is_parent' => $this->level + 1 < self::LEVEL_GRANDCHILD, // Only allow parent if not grandchild level
        ]);

        // Auto-generate account number based on level
        if (!isset($attributes['account_number']) || empty($attributes['account_number']))
        {
            $attributes['account_number'] = $this->getNextSubAccountNumber();
        }
        else
        {
            // Validate the provided account number format
            $this->validateSubAccountNumber($attributes['account_number']);
        }

        $account = self::create($attributes);

        if (!$this->is_parent)
        {
            $this->update(['is_parent' => true]);
        }

        return $account;
    }

    // Create methods for specific levels
    public static function createParentAccount(array $data): self
    {
        $data['level'] = self::LEVEL_PARENT;
        $data['is_parent'] = true;
        $data['parent_id'] = null;

        return self::create($data);
    }

    public function createChildAccount(array $data): self
    {
        if ($this->level !== self::LEVEL_PARENT)
        {
            throw new \Exception('Only parent accounts can have children');
        }

        $data['level'] = self::LEVEL_CHILD;
        $data['parent_id'] = $this->id;
        $data['is_parent'] = true; // Child can have grandchildren

        // Auto-generate child account number if not provided
        if (!isset($data['account_number']) || empty($data['account_number']))
        {
            $data['account_number'] = $this->getNextSubAccountNumber();
        }
        else
        {
            $this->validateSubAccountNumber($data['account_number']);
        }

        return self::create($data);
    }

    public function createGrandchildAccount(array $data): self
    {
        if ($this->level !== self::LEVEL_CHILD)
        {
            throw new \Exception('Only child accounts can have grandchildren');
        }

        $data['level'] = self::LEVEL_GRANDCHILD;
        $data['parent_id'] = $this->id;
        $data['is_parent'] = false; // Grandchildren cannot have children

        // Auto-generate grandchild account number if not provided
        if (!isset($data['account_number']) || empty($data['account_number']))
        {
            $data['account_number'] = $this->getNextSubAccountNumber();
        }
        else
        {
            $this->validateSubAccountNumber($data['account_number']);
        }

        return self::create($data);
    }

    public static function getValidParents($organizationId, $level = null): Collection
    {
        $query = self::where('is_parent', true)
            ->organizationAccounts($organizationId);

        // Filter by level for specific parent-child relationships
        if ($level !== null)
        {
            switch ($level)
            {
                case self::LEVEL_CHILD:
                    $query->where('level', self::LEVEL_PARENT);
                    break;
                case self::LEVEL_GRANDCHILD:
                    $query->where('level', self::LEVEL_CHILD);
                    break;
                default:
                    return collect();
            }
        }

        return $query->orderBy('account_number')->get();
    }
}
