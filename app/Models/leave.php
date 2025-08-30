<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
