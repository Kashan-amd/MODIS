<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'business_name',
        'contact_number',
        'contact_person',
        'address',
        'bm_official',
    ];

    /**
     * Get all accounts associated with the client across organizations.
     */
    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'client_accounts', 'client_id', 'account_number', 'id', 'account_number')
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
