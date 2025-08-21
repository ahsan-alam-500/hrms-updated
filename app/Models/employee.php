<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'emplyeetype',
        'eid',
        'fname',
        'lname',
        'nationalid',
        'dob',
        'level',
        'meritalstatus',
        'email',
        'phone',
        'emergencycontactname',
        'emergencycontactphone',
        'address',
        'designation',
        'department_id',
        'gender',
        'joindate',
        'probitionprioed',
        'reportingmanager',
        'workshift',
        'salary',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(department::class);
    }

    public function attendances()
    {
        return $this->hasMany(attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(leave::class);
    }

    public function payrolls()
    {
        return $this->hasMany(payroll::class);
    }

    public function documents()
    {
        return $this->hasMany(employeeDocument::class);
    }

    public function projectIncentives()
    {
        return $this->hasMany(ProjectIncentives::class);
    }
    public function personalHolidays()
    {
        return $this->hasMany(PersonalHoliday::class);
    }

    public function workingShifts()
    {
        return $this->hasMany(EmployeeHasShift::class);
    }
}
