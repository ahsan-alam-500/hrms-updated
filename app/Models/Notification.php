<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = "notifications";

    protected $fillable = [
        'action'
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_has_notification', 'notification_id', 'employee_id')
            ->withPivot('is_open')
            ->withTimestamps();
    }
}
