<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\PayrollController;
use App\Models\employee as Employee;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/forgotpassword', [AuthController::class, 'forgetPassword']);
    Route::post('/optvalidation', [AuthController::class,'optValidation']);
    Route::post('/resetpassword', [AuthController::class, 'resetPassword']);




    // Protected Routes
    Route::middleware('jwt.auth')->group(function () {

        // Auth
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        //Admin Summery data
        Route::apiResource('admin/dashboard', DashboardController::class);

        // Department CRUD
        Route::apiResource('departments', DepartmentController::class);

        //Shift CRUD
        Route::apiResource('shifts', ShiftController::class);

        // Employee CRUD
        Route::apiResource('employees', EmployeeController::class);
        Route::get('employee/attributes',[EmployeeController::class,'employeeAttributes']);

        // Attendance
        Route::apiResource('attendances', AttendanceController::class);
        Route::post('/attendance/filter', [AttendanceController::class, 'attendanceFilter']);
        Route::post('/attendance/filter/{id}', [AttendanceController::class, 'attendanceFilterPersonal']);
        Route::get('/employee/attendance/{id}', [AttendanceController::class, 'employeeAttendance']);
        Route::post('attendance/sync',[AttendanceController::class,'attendanceSyncer']);

        // Leave
        Route::apiResource('leaves', LeaveController::class);

        // Salary
        Route::apiResource('payrolls', PayrollController::class);

        // Documents
        Route::apiResource('documents', DocumentController::class);

        //Holiday
        Route::apiResource('holiday', HolidayController::class);

        //Notice
        Route::apiResource('notice', NoticeController::class);
    });



    //Automation routes (Dont Touch)
    // Attendance taking from machiene
    Route::post('/local/attendance', [AttendanceController::class, 'bulkStore']);

    //Setting Users to machien
    Route::get('/local/set/users', function () {
        $employees = Employee::all(); // fetch all employees

        $result = $employees->map(function($emp) {
            return [
                "uid" => (int) preg_replace('/\D/', '', $emp->eid),
                "userId" => (int) $emp->id,
                "name" => $emp->fname . " " . $emp->lname,
                "role" => 0,
                "password" => "",
                "cardno" => "000000000"
            ];
        });

        return response()->json($result);
    });


});
