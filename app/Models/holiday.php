<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class holiday extends Model
{
    protected $table = "holidays";
    protected $fillable = [
        'name',
        'date',
        'type',
        'description'
    ];
}
