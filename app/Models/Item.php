<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'category',
        'cogs_account_id',
    ];

    /**
     * Get the vendor that owns the item.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the COGS account associated with this item.
     */
    public function cogsAccount()
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }
}
