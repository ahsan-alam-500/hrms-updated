<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employeeTarget extends Model
{
    protected $table = "employee_targets";
    protected $fillable = [
        "employee_id",
        "quarter_id",
        "target_value",
        "achieved_value",
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }

    public function quarter()
    {
        return $this->belongsTo(quarter::class);
    }
}
