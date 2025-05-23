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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_balance' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'balance_date' => 'date',
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
}
