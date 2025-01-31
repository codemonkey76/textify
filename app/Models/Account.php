<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function destinations()
    {
        return $this->hasMany(Destination::class);
    }

    public function transcriptions()
    {
        return $this->hasMany(Transcription::class);
    }
}
