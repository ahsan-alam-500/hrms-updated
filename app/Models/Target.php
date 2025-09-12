<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $table = "targets";

    // ✅ Only fillable fields
    protected $fillable = [
        
        "target",
        "month",
        "year",
        "achieved"
    ];


}
