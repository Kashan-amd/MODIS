<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    protected $table = 'opening_balances';

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
