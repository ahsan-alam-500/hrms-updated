<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    protected $table = "bonuses";
    protected $fillable = [
        'employee_id',
        'quarter_id',
        'amount',
        'status'
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
