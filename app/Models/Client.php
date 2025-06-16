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
        'account_number',
    ];

    /**
     * Get the account associated with the client.
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_number', 'account_number');
    }
}
