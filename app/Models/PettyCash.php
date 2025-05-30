<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Akaunting\Money\Money;

class PettyCash extends Model
{
    use HasFactory;

    protected $table = 'petty_cash';

    protected $fillable = [
        'organization_id',
        'account_id',
        'balance',
        'debit',
        'credit',
        'reference',
        'description',
        'status',
        'transaction_date',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_VOID = 'void';

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    // Helper methods
    public function formatMoney($amount): string
    {
        return Money::PKR($amount * 100)->format();
    }

    public function getFormattedBalanceAttribute(): string
    {
        return $this->formatMoney($this->balance);
    }

    public function getFormattedDebitAttribute(): string
    {
        return $this->debit > 0 ? $this->formatMoney($this->debit) : '';
    }

    public function getFormattedCreditAttribute(): string
    {
        return $this->credit > 0 ? $this->formatMoney($this->credit) : '';
    }

    public function post()
    {
        $this->update(['status' => self::STATUS_POSTED]);

        // Update account balance
        $this->account->increment('current_balance', $this->debit - $this->credit);

        return $this;
    }

    public function void()
    {
        if ($this->status === self::STATUS_POSTED)
        {
            // Reverse the account balance update
            $this->account->decrement('current_balance', $this->debit - $this->credit);
        }

        $this->update(['status' => self::STATUS_VOID]);

        return $this;
    }
}
