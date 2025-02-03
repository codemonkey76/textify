<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected $casts = [
        'status' => MessageStatus::class
    ];
}
