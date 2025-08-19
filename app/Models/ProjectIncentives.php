<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectIncentives extends Model
{
    protected $table = "project_incentives";
    protected $fillable = ['employee_id', 'project_id', 'quarter', 'incentive_amount', 'remarks'];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
    public function projects()
    {
        return $this->belongsTo(Projects::class);
    }
}
