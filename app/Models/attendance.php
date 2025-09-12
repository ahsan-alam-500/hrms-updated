<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class attendance extends Model
{
    protected $table = "attendances";
     protected $fillable = [
        'employee_id',
        'status',
        'date',
        'in_time',
        'out_time',
        'late',
        'shift',
        'production_minutes',
        'overtime_hours'
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
    public function shift()
    {
        return $this->belongsTo(WorkingShift::class);
    }
}
