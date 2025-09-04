<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $table = 'organizations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the transactions where this organization is the sender.
     */
    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_organization_id');
    }

    /**
     * Get the transactions where this organization is the receiver.
     */
    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_organization_id');
    }
}
