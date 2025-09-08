<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeHasNotification extends Model
{
    protected $table = "employee_has_notification";

    protected $fillable = [
        'employee_id',
        'notification_id',
        'is_open'
    ];

    protected $casts = [
        'is_open' => 'boolean', // সবসময় true/false হিসেবে আসবে
    ];

    // Relation with Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Relation with Notification
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }
}
