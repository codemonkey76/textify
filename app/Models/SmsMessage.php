<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $guarded = [];


    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
