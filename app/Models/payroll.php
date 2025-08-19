<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'month',
        'basic_salary',
        'bonus',
        'deductions',
        'net_salary'
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
