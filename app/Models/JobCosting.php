<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCosting extends Model
{
    protected $table = 'job_costings';

    protected $guarded = [];

    protected $casts = [
        // Removed is_account cast as we no longer use account/item distinction
    ];

    public function jobBooking()
    {
        return $this->belongsTo(JobBooking::class, 'job_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subAccount()
    {
        return $this->belongsTo(Account::class, 'sub_account_id');
    }

    public function subItem()
    {
        return $this->belongsTo(Item::class, 'sub_item_id');
    }

    /**
     * Get the costing item name from sub_item_name field
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->sub_item_name ?? 'Unknown Item';
    }
}
