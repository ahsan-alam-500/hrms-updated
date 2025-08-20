<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\attendance as Attendance;
use App\Models\employee as Employee;
use App\Models\holiday as Holiday;
use App\Models\User;
use App\Models\leave as Leave;
use App\Models\PersonalHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    //show all attendance data and counter
    public function index()
    {
        $today = Carbon::today()->format('Y-m-d');
        $currentMonth = Carbon::now()->month;

        // Fetch all employees with user and attendances
        $employees = Employee::with(['user', 'attendances'])->get();

        // Fetch all public holidays in current month
        $holidayDates = Holiday::whereMonth('date', $currentMonth)
                                ->pluck('date')
                                ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
                                ->toArray();

        // Map employee data
        $data = $employees->map(function ($employee) {
            $attendanceData = [];
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLate = 0;

            foreach ($employee->attendances as $att) {
                $date = $att->date instanceof Carbon ? $att->date : Carbon::parse($att->date);
                $status = $att->status;

                // Late calculation: office hour 9:00 AM, late if in_time > 9:15 AM
                if ($att->in_time) {
                    $inTime = Carbon::createFromFormat('H:i:s', $att->in_time);
                    $officeStart = Carbon::createFromTime(9, 15);
                    if ($inTime->greaterThan($officeStart)) {
                        $status = 'late';
                        $totalLate++;
                    }
                }

                if ($status === 'present') $totalPresent++;
                elseif ($status === 'absent') $totalAbsent++;

                $attendanceData[] = [
                    'date' => $date->format('Y-m-d'),
                    'status' => $status,
                ];
            }

            return [
                'employee' => [
                    'id' => $employee->id,
                    'employee' => $employee, // full employee object
                ],
                'image' => $employee->user && $employee->user->image
                    ? url('public/'.$employee->user->image)
                    : url('/default-avatar.png'),
                'attendance' => $attendanceData,
                'summary' => [
                    'total_present' => $totalPresent,
                    'total_absent'  => $totalAbsent,
                    'total_late'    => $totalLate,
                ],
            ];
        });

        // Global counters
        $totalPresentToday = Attendance::where('date', $today)
                                       ->where('status', 'present')
                                       ->count();
        $totalAbsentToday = Attendance::where('date', $today)
                                      ->where('status', 'absent')
                                      ->count();

        $totalLateToday = Attendance::where('date', $today)
                                ->whereNotNull('in_time')
                                ->get()
                                ->filter(function ($att) {
                                    $inTime = Carbon::createFromFormat('H:i:s', $att->in_time);
                                    $officeStart = Carbon::createFromTime(9, 15);
                                    return $inTime->greaterThan($officeStart);
                                })->count();

        // Total working days = days in current month minus holidays
        $daysInMonth = Carbon::now()->daysInMonth;
        $totalWorkingDays = $daysInMonth - count($holidayDates);

        // Total approved leaves
        $totalLeave = Leave::where('status', 'approved')->count();

        return response()->json([
            'employees' => $data,
            'counters' => [
                'total_present_today' => $totalPresentToday,
                'total_absent_today'  => $totalAbsentToday,
                'totalLateToday'  => $totalLateToday,
                'total_leave'         => $totalLeave,
            ],
        ]);
    }

    // Show specific attendance
    public function show(Attendance $attendance)
    {
        $attendance->load('employee');
        return response()->json($attendance);
    }

    // Store attendance with holiday + overtime check
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date',
            'in_time'     => 'nullable|date_format:H:i',
            'out_time'    => 'nullable|date_format:H:i|after:in_time',
        ]);

        $date       = Carbon::parse($request->date)->toDateString();
        $employeeId = $request->employee_id;
        $status     = 'absent';
        $offreason  = null;
        $overtime   = 0;

        $isHoliday = Holiday::where('date', $date)->exists();
        $isPersonalHoliday = PersonalHoliday::where('employee_id', $employeeId)
                            ->where('date', $date)->exists();

        // 1. Holiday check
        if ($isHoliday || $isPersonalHoliday) {
            $offreason = $isHoliday ? 'Public Holiday' : 'Personal Holiday';

            // If worked on holiday -> Overtime
            if ($request->in_time && $request->out_time) {
                $status = 'present';

                $in  = Carbon::createFromFormat('H:i', $request->in_time);
                $out = Carbon::createFromFormat('H:i', $request->out_time);
                $overtime = $out->diffInHours($in); // overtime in hours
            } else {
                $status = 'holiday';
            }
        }
        // 2. Normal working day
        elseif ($request->in_time && $request->out_time) {
            $inTime = Carbon::createFromFormat('H:i', $request->in_time);
            $officeStart = Carbon::createFromTime(9, 15); // 9:15 AM

            if ($inTime->greaterThan($officeStart)) {
                $status = 'late';
            } else {
                $status = 'present';
            }
        }

        // Save / Update Attendance
        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'date'        => $date,
            ],
            [
                'in_time'        => $request->in_time,
                'out_time'       => $request->out_time,
                'status'         => $status,
                'off_reason'     => $offreason,
                'overtime_hours' => $overtime,
            ]
        );

        $attendance->load('employee');
        return response()->json($attendance, 201);
    }
}
