<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class department extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function employees(){
      return $this->hasMany(employee::class);
    }
}
