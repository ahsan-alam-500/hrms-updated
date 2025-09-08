<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Objection extends Model
{
    protected $table = "objections";
    protected $fillable = [
        'subject',
        'objection',
        'is_seen'
    ];
}
