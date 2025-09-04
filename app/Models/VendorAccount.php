<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorAccount extends Model
{
    use HasFactory;

    protected $table = 'vendor_accounts';

    protected $fillable = [
        'vendor_id',
        'account_number',
        'organization_id',
    ];

    /**
     * Get the vendor that owns this account relationship.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
