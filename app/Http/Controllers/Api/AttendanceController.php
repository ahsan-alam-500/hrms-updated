<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\attendance as Attendance;
use App\Models\employee as Employee;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // Show all attendances with employee details
    public function index()
    {
        $attendances = Attendance::with('employee')->get();
        return response()->json($attendances);
    }

    // Show a specific attendance with employee info
    public function show(Attendance $attendance)
    {
        $attendance->load('employee');
        return response()->json($attendance);
    }

    // Store new attendance
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'in_time' => 'required|date_format:H:i',
            'out_time' => 'required|date_format:H:i|after:in_time',
        ]);

        $attendance = Attendance::create([
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'in_time' => $request->in_time,
            'out_time' => $request->out_time,
        ]);

        $attendance->load('employee'); // include employee in response
        return response()->json($attendance, 201);
    }
}
