<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalHoliday extends Model
{
    protected $table = "personal_holidays";
    protected $fillable = [
        'name',
        'employee_id',
        'holidays',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
