<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCosting extends Model
{
    protected $table = 'job_costings';

    protected $guarded = [];

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
}
