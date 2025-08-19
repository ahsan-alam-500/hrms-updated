<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayrollController;
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

        // Department CRUD
        Route::apiResource('departments', DepartmentController::class);

        // Employee CRUD
        Route::apiResource('employees', EmployeeController::class);

        // Attendance
        Route::apiResource('attendances', AttendanceController::class);

        // Leave
        Route::apiResource('leaves', LeaveController::class);

        // Salary
        Route::apiResource('payrolls', PayrollController::class);

        // Documents
        Route::apiResource('documents', DocumentController::class);
    });
});
