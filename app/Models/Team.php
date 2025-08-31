<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = "project_teams";

    protected $fillable = [
        'team_name',
        'team_leader',   // employee_id
        'team_formed'
    ];

    // A team has many employees
    public function teamEmployees()
    {
        return $this->hasMany(TeamHasEmployee::class, 'team_id');
    }
}
