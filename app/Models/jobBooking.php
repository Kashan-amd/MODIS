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

    public function generateJobNumber($orgId): string
    {
        $latestJob = self::orderBy('id', 'desc')->first();
        $nextId = $latestJob ? $latestJob->id + 1 : 1;

        // Get organization name safely
        $organization = Organization::find($orgId);
        $organizationName = $organization->name;
        // Extract organization initials - first 2 letters and first letter of second word if it exists
        $orgParts = explode(' ', $organizationName);
        $initials = '';
        if (count($orgParts) > 0)
        {
            $initials .= strtoupper(substr($orgParts[0], 0, 2)); // First two letters of the first word
            if (count($orgParts) > 1)
            {
                $initials .= strtoupper(substr($orgParts[1], 0, 1)); // First letter of the second word
            }
        }
        else
        {
            $initials = strtoupper(substr($organizationName, 0, 3)); // Fallback to first three letters if no space
        }
        // Generate job number in the format: ORG-J-01
        return $initials . '-J-' . str_pad($nextId, 2, '0', STR_PAD_LEFT);
    }

    public function getStatus($value): string
    {
        return $value === 'open' ? 'Open' : 'Closed';
    }
}
