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
        'client'
    ];

    public function projectsincentives(){
        return $this->hasMany(ProjectIncentives::class);
    }


}
