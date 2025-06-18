<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'category',
        'contact_person',
        'contact_number',
        'address',
    ];

    /**
     * Get all accounts associated with the vendor across organizations.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'vendor_accounts', 'vendor_id', 'account_number', 'id', 'account_number')
            ->withPivot('organization_id')
            ->withTimestamps();
    }

    /**
     * Get accounts for a specific organization.
     */
    public function accountsForOrganization($organizationId)
    {
        return $this->accounts()->wherePivot('organization_id', $organizationId);
    }

    /**
     * Get the primary account for a specific organization (if needed).
     */
    public function primaryAccountForOrganization($organizationId)
    {
        return $this->accountsForOrganization($organizationId)->first();
    }
}
