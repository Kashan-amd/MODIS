<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientAccount extends Model
{
    use HasFactory;

    protected $table = 'client_accounts';

    protected $fillable = [
        'client_id',
        'account_number',
        'organization_id',
    ];

    /**
     * Get the client that owns this account relationship.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the account associated with this relationship.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_number', 'account_number');
    }

    /**
     * Get the organization associated with this relationship.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
