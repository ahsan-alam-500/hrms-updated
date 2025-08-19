<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quarter extends Model
{
    protected $table = "quarters";
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }
    public function targets()
    {
        return $this->hasMany(employeeTarget::class);
    }
}
