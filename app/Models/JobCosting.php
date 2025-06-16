<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCosting extends Model
{
    protected $table = 'job_costings';

    protected $guarded = [];

    protected $casts = [
        'is_account' => 'boolean',
    ];

    public function jobBooking()
    {
        return $this->belongsTo(JobBooking::class, 'job_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'item_id');
    }

    public function subAccount()
    {
        return $this->belongsTo(Account::class, 'sub_account_id');
    }

    public function subItem()
    {
        return $this->belongsTo(Account::class, 'sub_item_id');
    }

    /**
     * Get the name of the item, whether it's an Item or an Account
     *
     * @return string
     */
    public function getItemName()
    {
        if ($this->is_account)
        {
            $account = $this->account;
            return $account ? $account->name . ' (' . $account->account_number . ')' : 'Unknown Account';
        }
        else
        {
            $item = $this->item;
            return $item ? $item->name : 'Unknown Item';
        }
    }
}
