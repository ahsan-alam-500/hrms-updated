<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Models\employee as Employee;
use App\Models\attendance as Attendance;


Route::get('/clear-logs', function() {
    $files = glob(storage_path('logs/*.log'));
    foreach($files as $file){
        unlink($file);
    }
    return "Logs cleared!";
});


Route::get('/attendance',function(){
    $employees = Employee::all();
    $attendances = Attendance::with('employee')->get();
    return view('attendence',compact('employees','attendances'));
})->name('attendance.index');
Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');