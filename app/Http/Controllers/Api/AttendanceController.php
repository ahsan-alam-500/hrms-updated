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
    // public function index()
    // {
    //     $today = Carbon::today()->format('Y-m-d');
    //     $currentMonth = Carbon::now()->month;

    //     // Fetch all employees with user and attendances
    //     $employees = Employee::with(['user', 'attendances'])->get();

    //     // Fetch all public holidays in current month
    //     $holidayDates = Holiday::whereMonth('date', $currentMonth)
    //                             ->pluck('date')
    //                             ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
    //                             ->toArray();

    //     // Map employee data
    //     $data = $employees->map(function ($employee) {
    //         $attendanceData = [];
    //         $totalPresent = 0;
    //         $totalAbsent = 0;
    //         $totalLate = 0;

    //         foreach ($employee->attendances as $att) {
    //             $date = $att->date instanceof Carbon ? $att->date : Carbon::parse($att->date);
    //             $status = $att->status;

    //             // Late calculation: office hour 9:00 AM, late if in_time > 9:15 AM
    //             if ($att->in_time) {
    //                 $inTime = Carbon::createFromFormat('H:i:s', $att->in_time);
    //                 $officeStart = Carbon::createFromTime(9, 15);
    //                 if ($inTime->greaterThan($officeStart)) {
    //                     $status = 'Late';
    //                     $totalLate++;
    //                 }
    //             }

    //             if ($status === 'Present') $totalPresent++;
    //             elseif ($status === 'Absent') $totalAbsent++;

    //             $attendanceData[] = [
    //                 'date' => $date->format('Y-m-d'),
    //                 'status' => $status,
    //             ];
    //         }

    //         return [
    //             'employee' => [
    //                 'id' => $employee->id,
    //                 'employee' => $employee, // full employee object
    //             ],
    //             'image' => $employee->user && $employee->user->image
    //                 ? url('public/'.$employee->user->image)
    //                 : url('/default-avatar.png'),
    //             'attendance' => $attendanceData,
    //             'summary' => [
    //                 'total_present' => $totalPresent,
    //                 'total_absent'  => $totalAbsent,
    //                 'total_late'    => $totalLate,
    //             ],
    //         ];
    //     });

    //     // Global counters
    //     $totalPresentToday = Attendance::where('date', $today)
    //                                   ->where('status', 'Present')
    //                                   ->count();
    //     $totalAbsentToday = Attendance::where('date', $today)
    //                                   ->where('status', 'Absent')
    //                                   ->count();

    //     $totalLateToday = Attendance::where('date', $today)
    //                             ->whereNotNull('in_time')
    //                             ->get()
    //                             ->filter(function ($att) {
    //                                 $inTime = Carbon::createFromFormat('H:i:s', $att->in_time);
    //                                 $officeStart = Carbon::createFromTime(9, 15);
    //                                 return $inTime->greaterThan($officeStart);
    //                             })->count();

    //     // Total working days = days in current month minus holidays
    //     $daysInMonth = Carbon::now()->daysInMonth;
    //     $totalWorkingDays = $daysInMonth - count($holidayDates);

    //     // Total approved leaves
    //     $totalLeave = Leave::where('status', 'approved')->count();

    //     return response()->json([
    //         'employees' => $data,
    //         'counters' => [
    //             'total_present_today' => $totalPresentToday,
    //             'total_absent_today'  => $totalAbsentToday,
    //             'totalLateToday'  => $totalLateToday,
    //             'total_leave'         => $totalLeave,
    //         ],
    //     ]);
    // }


    public function index()
{
    $today        = Carbon::today();
    $currentYear  = $today->year;
    $currentMonth = $today->month;
    $monthStart   = Carbon::create($currentYear, $currentMonth, 1)->startOfDay();
    $monthEnd     = (clone $monthStart)->endOfMonth();

    // -------- Prefetch --------
    $employees = Employee::with(['user'])->get();

    $attendances = Attendance::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
        ->get()
        ->groupBy('employee_id');

    // Holidays (Y-m-d set)
    $holidayDates = Holiday::whereBetween('date', [$monthStart, $monthEnd])
        ->pluck('date')
        ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
        ->toArray();
    $holidaySet = array_flip($holidayDates);

    // Approved leaves → map per-employee per-date (only the part inside current month)
    $approvedLeaves = Leave::where('status', 'approved')
        ->where(function ($q) use ($monthStart, $monthEnd) {
            $q->whereBetween('start_date', [$monthStart, $monthEnd])
              ->orWhereBetween('end_date',   [$monthStart, $monthEnd])
              ->orWhere(function ($q2) use ($monthStart, $monthEnd) {
                  $q2->where('start_date', '<=', $monthStart)->where('end_date', '>=', $monthEnd);
              });
        })->get();

    $leaveMap = []; // employee_id => ['Y-m-d' => true, ...]
    foreach ($approvedLeaves as $lv) {
        $empId = $lv->employee_id;
        $s = Carbon::parse($lv->start_date)->startOfDay();
        $e = Carbon::parse($lv->end_date)->endOfDay();

        $from = $s->lt($monthStart) ? (clone $monthStart) : (clone $s);
        $to   = $e->gt($monthEnd)   ? (clone $monthEnd)   : (clone $e);

        if (!isset($leaveMap[$empId])) $leaveMap[$empId] = [];
        for ($d = (clone $from); $d->lte($to); $d->addDay()) {
            $leaveMap[$empId][$d->format('Y-m-d')] = true;
        }
    }

    // -------- Rules --------
    $officeLateCutoff = Carbon::createFromTime(9, 15, 0); // 09:15
    $halfHoursThresh  = 4; // < 4h => Half

    // -------- Build per-employee month view --------
    $data = $employees->map(function ($employee) use (
        $monthStart, $monthEnd, $attendances, $holidaySet, $leaveMap, $officeLateCutoff, $halfHoursThresh, $today
    ) {
        // Attendance rows indexed by date
        $attendanceByDate = [];
        if (isset($attendances[$employee->id])) {
            foreach ($attendances[$employee->id] as $att) {
                $key = Carbon::parse($att->date)->format('Y-m-d');
                $attendanceByDate[$key] = $att;
            }
        }

        $attendanceData = [];
        $summary = [
            'present' => 0,
            'absent'  => 0,
            'late'    => 0,
            'half'    => 0,
            'leave'   => 0,
            'holiday' => 0,
        ];

        for ($d = (clone $monthStart); $d->lte($monthEnd); $d->addDay()) {
            $dateStr = $d->format('Y-m-d');

            // future date skip
            if ($d->gt($today)) continue;

            // Priority 1: Holiday
            if (isset($holidaySet[$dateStr])) {
                $status = 'Holiday';
                $summary['holiday']++;
            }
            // Priority 2: Leave
            elseif (isset($leaveMap[$employee->id]) && isset($leaveMap[$employee->id][$dateStr])) {
                $status = 'Leave';
                $summary['leave']++;
            }
            // Else attendance-derived
            elseif (isset($attendanceByDate[$dateStr])) {
                $att = $attendanceByDate[$dateStr];

                // If DB already has any known status, normalize and respect
                $raw = $att->status ? trim($att->status) : null;
                $normalized = $raw ? strtolower($raw) : null;

                $final = null;
                if (in_array($normalized, [
                    'present','absent','late','half','half day','half_day','leave','on leave','on_leave','holiday'
                ])) {
                    $map = [
                        'present'  => 'Present',
                        'absent'   => 'Absent',
                        'late'     => 'Late',
                        'half'     => 'Half',
                        'half day' => 'Half',
                        'half_day' => 'Half',
                        'leave'    => 'Leave',
                        'on leave' => 'Leave',
                        'on_leave' => 'Leave',
                        'holiday'  => 'Holiday',
                    ];
                    $final = $map[$normalized];
                } else {
                    // derive from in/out time
                    $in  = $att->in_time  ? Carbon::createFromFormat('H:i:s', $att->in_time)  : null;
                    $out = $att->out_time ? Carbon::createFromFormat('H:i:s', $att->out_time) : null;

                    if ($in && $out) {
                        $workedHrs = $in->diffInMinutes($out) / 60;
                        if ($workedHrs < $halfHoursThresh) {
                            $final = 'Half';
                        } elseif ($in->gt($officeLateCutoff)) {
                            $final = 'Late';
                        } else {
                            $final = 'Present';
                        }
                    } elseif ($in && !$out) {
                        // partial punch → noon-এর পরে হলে Half, নইলে Late/Present by in time
                        if (Carbon::now()->gt($d->copy()->setTime(12,0))) {
                            $final = 'Half';
                        } else {
                            $final = $in->gt($officeLateCutoff) ? 'Late' : 'Present';
                        }
                    } else {
                        $final = 'Absent';
                    }
                }

                $status = $final;

                // tally
                if     ($status === 'Present') $summary['present']++;
                elseif ($status === 'Absent')  $summary['absent']++;
                elseif ($status === 'Late')    $summary['late']++;
                elseif ($status === 'Half')    $summary['half']++;
                elseif ($status === 'Leave')   $summary['leave']++;
                elseif ($status === 'Holiday') $summary['holiday']++;
            }
            // No holiday/leave/attendance → Absent
            else {
                $status = 'Absent';
                $summary['absent']++;
            }

            $attendanceData[] = [
                'date'   => $dateStr,
                'status' => $status, // Present|Absent|Late|Half|Leave|Holiday
            ];
        }

        return [
            'employee' => [
                'id'       => $employee->id,
                'employee' => $employee,
            ],
            'image' => ($employee->user && $employee->user->image)
                ? url('public/'.$employee->user->image)
                : url('/default-avatar.png'),
            'attendance' => $attendanceData,
            'summary'    => [
                'total_present' => $summary['present'],
                'total_absent'  => $summary['absent'],
                'total_late'    => $summary['late'],
                'total_half'    => $summary['half'],
                'total_leave'   => $summary['leave'],
                'total_holiday' => $summary['holiday'],
            ],
        ];
    });

    // -------- Global counters (today) --------
    $todayStr = $today->format('Y-m-d');

    $isHolidayToday = isset($holidaySet[$todayStr]);

    // who's on leave today
    $onLeaveTodayEmpIds = [];
    foreach ($leaveMap as $empId => $set) {
        if (isset($set[$todayStr])) $onLeaveTodayEmpIds[] = $empId;
    }

    $attsToday = Attendance::whereDate('date', $todayStr)->get()->groupBy('employee_id');

    $countersToday = [
        'Present' => 0,
        'Absent'  => 0,
        'Late'    => 0,
        'Half'    => 0,
        'Leave'   => 0,
        'Holiday' => 0,
    ];

    if ($isHolidayToday) {
        // Optional: সবার জন্য Holiday ধরে নিতে পারেন
        $countersToday['Holiday'] = $employees->count();
    } else {
        $countersToday['Leave'] = count($onLeaveTodayEmpIds);

        foreach ($employees as $emp) {
            if (in_array($emp->id, $onLeaveTodayEmpIds)) continue; // already counted as Leave

            $status = null;

            if (isset($attsToday[$emp->id])) {
                /** @var \App\Models\Attendance $row */
                $row = $attsToday[$emp->id]->first();

                // prefer stored status if valid
                $raw = $row->status ? strtolower(trim($row->status)) : null;
                if (in_array($raw, ['present','absent','late','half','half day','half_day'])) {
                    $map = [
                        'present'  => 'Present',
                        'absent'   => 'Absent',
                        'late'     => 'Late',
                        'half'     => 'Half',
                        'half day' => 'Half',
                        'half_day' => 'Half',
                    ];
                    $status = $map[$raw];
                } else {
                    // derive
                    $in  = $row->in_time  ? Carbon::createFromFormat('H:i:s', $row->in_time)  : null;
                    $out = $row->out_time ? Carbon::createFromFormat('H:i:s', $row->out_time) : null;

                    if ($in && $out) {
                        $workedHrs = $in->diffInMinutes($out) / 60;
                        if ($workedHrs < $halfHoursThresh) {
                            $status = 'Half';
                        } elseif ($in->gt($officeLateCutoff)) {
                            $status = 'Late';
                        } else {
                            $status = 'Present';
                        }
                    } elseif ($in && !$out) {
                        if (Carbon::now()->gt($today->copy()->setTime(12,0))) {
                            $status = 'Half';
                        } else {
                            $status = $in->gt($officeLateCutoff) ? 'Late' : 'Present';
                        }
                    } else {
                        $status = 'Absent';
                    }
                }
            } else {
                $status = 'Absent';
            }

            $countersToday[$status]++;
        }
    }

    // আপনার আগের মত—সব approved leave এর টোটাল
    $totalLeaveApproved = Leave::where('status', 'approved')->count();

    return response()->json([
        'employees' => $data,
        'counters'  => [
            'present_today' => $countersToday['Present'],
            'absent_today'  => $countersToday['Absent'],
            'late_today'    => $countersToday['Late'],
            'half_today'    => $countersToday['Half'],
            'leave_today'   => $countersToday['Leave'],
            'holiday_today' => $countersToday['Holiday'],
            'total_leave'   => $totalLeaveApproved,
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
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'employee_id' => 'required|exists:employees,id',
    //         'date'        => 'required|date',
    //         'in_time'     => 'nullable|date_format:H:i',
    //         'out_time'    => 'nullable|date_format:H:i|after:in_time',
    //     ]);

    //     $date       = Carbon::parse($request->date)->toDateString();
    //     $employeeId = $request->employee_id;
    //     $status     = 'absent';
    //     $offreason  = null;
    //     $overtime   = 0;

    //     $isHoliday = Holiday::where('date', $date)->exists();
    //     $isPersonalHoliday = PersonalHoliday::where('employee_id', $employeeId)
    //                         ->where('date', $date)->exists();

    //     // 1. Holiday check
    //     if ($isHoliday || $isPersonalHoliday) {
    //         $offreason = $isHoliday ? 'Public Holiday' : 'Personal Holiday';

    //         // If worked on holiday -> Overtime
    //         if ($request->in_time && $request->out_time) {
    //             $status = 'present';

    //             $in  = Carbon::createFromFormat('H:i', $request->in_time);
    //             $out = Carbon::createFromFormat('H:i', $request->out_time);
    //             $overtime = $out->diffInHours($in); // overtime in hours
    //         } else {
    //             $status = 'holiday';
    //         }
    //     }
    //     // 2. Normal working day
    //     elseif ($request->in_time && $request->out_time) {
    //         $inTime = Carbon::createFromFormat('H:i', $request->in_time);
    //         $officeStart = Carbon::createFromTime(9, 15); // 9:15 AM

    //         if ($inTime->greaterThan($officeStart)) {
    //             $status = 'late';
    //         } else {
    //             $status = 'present';
    //         }
    //     }

    //     // Save / Update Attendance
    //     $attendance = Attendance::updateOrCreate(
    //         [
    //             'employee_id' => $employeeId,
    //             'date'        => $date,
    //         ],
    //         [
    //             'in_time'        => $request->in_time,
    //             'out_time'       => $request->out_time,
    //             'status'         => $status,
    //             'off_reason'     => $offreason,
    //             'overtime_hours' => (start to end )-8 hours,
    //         ]
    //     );

    //     $attendance->load('employee');
    //     return response()->json($attendance, 201);
    // }


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
        $status     = 'Absent';
        $offreason  = null;
        $overtime   = 0;

        $isHoliday = Holiday::where('date', $date)->exists();
        $isPersonalHoliday = PersonalHoliday::where('employee_id', $employeeId)
                            ->where('date', $date)->exists();

        if ($request->in_time && $request->out_time) {
            $in  = Carbon::createFromFormat('H:i', $request->in_time);
            $out = Carbon::createFromFormat('H:i', $request->out_time);

            $workedHours = $out->diffInMinutes($in) / 60; // total worked hours in decimal

            // Overtime = hours worked minus 8 hours
            $overtime = max(0, $workedHours - 8);

            // Normal working day status
            $officeStart = Carbon::createFromTime(9, 15); // 9:15 AM
            if (!$isHoliday && !$isPersonalHoliday) {
                $status = $in->greaterThan($officeStart) ? 'Late' : 'Present';
            } else {
                // Holiday worked
                $status = 'Present';
                $offreason = $isHoliday ? 'Public Holiday' : 'Personal Holiday';
            }
        } else {
            // No in/out time
            if ($isHoliday || $isPersonalHoliday) {
                $status = 'Holiday';
                $offreason = $isHoliday ? 'Public Holiday' : 'Personal Holiday';
            } else {
                $status = 'Absent';
            }
        }

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
                'overtime_hours' => round($overtime, 2), // optional: round to 2 decimals
            ]
        );

        $attendance->load('employee');
        return response()->json($attendance, 201);
    }

}
