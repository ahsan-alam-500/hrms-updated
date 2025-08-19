<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class employeeDocument extends Model
{
    protected $table = 'employee_documents';
    protected $fillable = ['employee_id', 'type', 'file_path'];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
}
