<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingShift extends Model
{
    protected $table = "working_shifts";

    // ✅ Only fillable fields
    protected $fillable = [
        "shift_name",
        "start_time",
        "end_time",
        "grace_time",
    ];

    // ✅ Casts for easier time handling
    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
        'grace_time' => 'integer', // in minutes
    ];
    
    public function employee()
    {
        return $this->hasOne(employee::class);
    }

    // ✅ Relationships
    public function employeeHasShift()
    {
        return $this->hasMany(EmployeeHasShift::class, 'working_shift_id');
    }

    public function attendance()
    {
        return $this->hasMany(attendance::class);
    }

}
