<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\employee as Employee;
use App\Models\Team;

class TeamHasEmployee extends Model
{
    protected $table = "team_has_employees";

    protected $fillable = [
        'team_id',
        'employee_id'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
