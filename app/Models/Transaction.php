<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Akaunting\Money\Money;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // Original fields for organization transfers
        'from_organization_id',
        'to_organization_id',
        'amount',
        'transaction_type',
        'transaction_date',

        // New fields for accounting transactions
        'date',
        'reference',
        'description',
        'status',
        'organization_id',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'date' => 'date',
    ];

    /**
     * Transaction statuses
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_VOID = 'void';

    /**
     * Get the organization that sent the transaction.
     */
    public function fromOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'from_organization_id');
    }

    /**
     * Get the organization that received the transaction.
     */
    public function toOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'to_organization_id');
    }

    /**
     * Get the organization this transaction belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created this transaction.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the transaction entries.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * Get the job booking this transaction belongs to.
     */
    public function jobBooking(): BelongsTo
    {
        return $this->belongsTo(JobBooking::class);
    }

    /**
     * Format money values.
     */
    public function formatMoney($amount): string
    {
        return Money::PKR($amount * 100)->format();
    }

    /**
     * Get formatted amount attribute.
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->formatMoney($this->amount);
    }

    /**
     * Check if the transaction is a journal entry.
     */
    public function isJournalEntry(): bool
    {
        return $this->transaction_type === 'journal';
    }

    /**
     * Check if the transaction is an organizational transfer.
     */
    public function isOrganizationalTransfer(): bool
    {
        return $this->from_organization_id && $this->to_organization_id;
    }

    /**
     * Create a reversal transaction for this transaction.
     * This creates a new transaction that is the exact opposite of this one.
     * Only posted transactions can be reversed.
     */
    public function createReversal($reference = null, $description = null): Transaction
    {
        // Only allow reversing posted transactions
        if ($this->status !== self::STATUS_POSTED)
        {
            throw new \Exception('Only posted transactions can be reversed');
        }

        // Start a database transaction
        DB::beginTransaction();

        try
        {
            // Create the reversal transaction
            $reversal = new Transaction();
            $reversal->fill([
                'date' => now(),
                'reference' => $reference ?? 'Reversal of ' . $this->reference,
                'description' => $description ?? 'Reversal of transaction #' . $this->id . ' - ' . $this->description,
                'status' => self::STATUS_POSTED, // Reversals are posted immediately
                'organization_id' => $this->organization_id,
                'created_by' => auth()->id(),
            ]);
            $reversal->save();

            // Create reversal entries for each original entry
            foreach ($this->entries as $entry)
            {
                // Create an entry with debit and credit swapped
                $reversalEntry = $reversal->entries()->create([
                    'account_id' => $entry->account_id,
                    'description' => 'Reversal of ' . $entry->description,
                    'debit' => $entry->credit,
                    'credit' => $entry->debit,
                    'amount' => -$entry->amount,
                ]);

                // Update the account balance (reverse the original effect)
                $account = Account::find($entry->account_id);

                // Reverse the original amount effect
                if (in_array($account->type, ['asset', 'expense']))
                {
                    $account->current_balance -= $entry->amount;
                }
                else
                {
                    $account->current_balance += $entry->amount;
                }

                $account->save();
            }

            DB::commit();
            return $reversal;
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Post a draft transaction and update account balances.
     */
    public function post(): bool
    {
        if ($this->status !== self::STATUS_DRAFT)
        {
            return false; // Can only post draft transactions
        }

        DB::beginTransaction();
        try
        {
            // Update account balances
            foreach ($this->entries as $entry)
            {
                $account = $entry->account;
                $amount = $entry->amount;

                // Update account balance based on account type
                if (in_array($account->type, ['asset', 'expense']))
                {
                    // For Asset and Expense accounts:
                    // - Positive amount increases the balance
                    // - Negative amount decreases the balance
                    $account->current_balance += $amount;
                }
                else
                {
                    // For Liability, Equity, and Income accounts:
                    // - Positive amount decreases the balance (credit increases liability/equity/income)
                    // - Negative amount increases the balance
                    $account->current_balance -= $amount;
                }

                $account->save();
            }

            // Update transaction status
            $this->status = self::STATUS_POSTED;
            $this->save();

            DB::commit();
            return true;
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            throw $e;
        }
    }
}
