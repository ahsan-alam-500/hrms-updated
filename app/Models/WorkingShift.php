<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingShift extends Model
{
    protected $table = "working_shifts";
    protected $fillable = ["id","shift_name", "start_time", "end_time", "grace_time", "created_at", "updated_at"];

    public function workingShifts()
    {
        return $this->hasMany(EmployeeHasShift::class);
    }
}
