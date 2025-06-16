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
        'account_number',
    ];

    /**
     * Get the account associated with the vendor.
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_number', 'account_number');
    }
}
