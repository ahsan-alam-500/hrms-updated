<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\employee as Employee;

class ProjectHasEmployee extends Model
{
    protected $table = "project_has_employee";

    protected $fillable = [
        'project_id',
        'team_leader',  // can store JSON if multiple leaders
        'employee_id',
        'destribution'
    ];

    // Automatically cast team_leader as an array for easy access
    protected $casts = [
        'team_leader' => 'array',
    ];

    /**
     * Relationship: each pivot belongs to an Employee
     * Eager load the related user automatically
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->with('user');
    }
}
