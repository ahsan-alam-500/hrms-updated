<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meeting extends Model
{
    use HasFactory;

    protected $table = "meetings";

    protected $fillable = [
        "user_id",
        "title",
        "description",
        "time",
        "is_done"
    ];

    // Relation: Meeting created by User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
