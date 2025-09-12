<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employee extends Model
{
    protected $fillable = [
        'id',
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
        return $this->belongsTo(User::class, 'user_id');
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

    public function workingshift() 
    {
    return $this->belongsTo(WorkingShift::class, 'workshift');
    }

    public function project() 
    {
    return $this->hasMany(ProjectHasEmployee::class);
    }
    
    public function shifts()
    {
        return $this->hasMany(EmployeeHasShift::class, 'employee_id');
    }
    
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'employee_has_notification', 'employee_id', 'notification_id')
                    ->withPivot('is_open')
                    ->withTimestamps();
    }
    
    public function assignedProjects()
    {
        return $this->belongsToMany(
            Projects::class, 
            'project_has_employee',
            'employee_id',          
            'project_id'  
        );
    }
    
    public function meetings()
    {
        return $this->belongsToMany(
            Meeting::class,
            'meeting_has_employees',
            'employee_id',
            'meeting_id'
        );
    }


}
