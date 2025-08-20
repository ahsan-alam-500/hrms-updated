<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\attendance as Attendance;
use App\Models\employee as Employee;
use App\Models\holiday as Holiday;
use App\Models\PersonalHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // All attendances with employee details
    public function index()
    {
        $attendances = Attendance::with('employee')->get();
        return response()->json($attendances);
    }

    // Show specific attendance
    public function show(Attendance $attendance)
    {
        $attendance->load('employee');
        return response()->json($attendance);
    }

    // Store attendance with holiday check
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'in_time'     => 'nullable|date_format:H:i',
            'out_time'    => 'nullable|date_format:H:i|after:in_time',
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $employeeId = $request->employee_id;

        // 1. Check if public holiday
        if (Holiday::where('date', $date)->exists()) {
            $status = 'holiday';
        }
        // 2. Check if personal holiday
        elseif (PersonalHoliday::where('employee_id', $employeeId)->where('date', $date)->exists()) {
            $status = 'holiday';
        }
        // 3. Otherwise normal attendance
        elseif ($request->in_time && $request->out_time) {
            $status = 'present';
        } else {
            $status = 'absent';
        }

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date'        => $date,
            ],
            [
                'in_time'  => $request->in_time,
                'out_time' => $request->out_time,
                'status'   => $status,
            ]
        );

        $attendance->load('employee');
        return response()->json($attendance, 201);
    }
}
