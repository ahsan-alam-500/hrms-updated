<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeHasShift extends Model
{
    protected $table = "employee_has_shift";
    protected $fillable = ['employee_id', 'working_shift_id'];
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(WorkingShift::class, 'working_shift_id');
    }
}
