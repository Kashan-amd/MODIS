<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Akaunting\Money\Money;

class TransactionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'amount',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function formatMoney($amount): string
    {
        return Money::PKR($amount * 100)->format();
    }

    public function getFormattedDebitAttribute(): string
    {
        return $this->debit > 0 ? $this->formatMoney($this->debit) : '';
    }

    public function getFormattedCreditAttribute(): string
    {
        return $this->credit > 0 ? $this->formatMoney($this->credit) : '';
    }
}
