<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    protected $table = "projects";

    protected $fillable = [
        'name',
        'description',
        'status',
        'start_date',
        'end_date',
        'project_manager',
        'team_name',
        'team_leader',
        'client',
        'Department',
        'priority'
    ];

    public function projectsincentives()
    {
        return $this->hasMany(ProjectIncentives::class);
    }

    public function assignedEmployees()
    {
        return $this->hasMany(ProjectHasEmployee::class, 'project_id')
                    ->with('employee.user');
    }

    public function projectManager()
    {
        return $this->belongsTo(Employee::class, 'project_manager')
                    ->with('user');
    }
}
