<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MeetingHasEmployee extends Model
{
    use HasFactory;

    protected $table = "meeting_has_employees";

    protected $fillable = [
        "meeting_id",
        "employee_id",
    ];
}
