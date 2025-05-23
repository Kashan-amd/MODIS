<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class jobBooking extends Model
{
    protected $table = 'job_bookings';

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function jobCostings()
    {
        return $this->hasMany(JobCosting::class, 'job_id');
    }

    public function generateJobNumber()
    {
        $latestJob = self::orderBy('id', 'desc')->first();
        $nextId = $latestJob ? $latestJob->id + 1 : 1;
        return 'JOB-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }
    public function getStatusAttribute($value)
    {
        return $value === 'open' ? 'Open' : 'Closed';
    }
}
