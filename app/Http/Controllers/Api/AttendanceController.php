<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\attendance as Attendance;
use App\Models\employee as Employee;
use App\Models\EmployeeHasShift;
use App\Models\WorkingShift;
use App\Models\holiday as Holiday;
use App\Models\User;
use App\Models\leave as Leave;
use App\Models\department;
use App\Models\PersonalHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()
            ->startOfMonth()
            ->toDateString();
        $endOfMonth = Carbon::now()
            ->endOfMonth()
            ->toDateString();

        $allStatuses = [
            "Holiday",
            "Half",
            "Absent",
            "Present",
            "Leave",
            "Late",
        ];

        // Count holidays in the current month
        $holidayCount = Holiday::whereBetween("date", [
            $startOfMonth,
            $endOfMonth,
        ])->count();

        // Total days in month
        $totalDays = Carbon::now()->daysInMonth;

        // Total working days = month days - holidays
        $workingDays = max(1, $totalDays - $holidayCount);

        // Load employees with shifts and user info
        $employees = Employee::with(["user"])->get([
            "id",
            "fname",
            "lname",
            "eid",
            "user_id",
            "workshift",
        ]);

        $result = [];

        foreach ($employees as $employee) {
            // Attendance counts for current month
            $statusCounts = Attendance::where("employee_id", $employee->id)
                ->whereBetween("date", [$startOfMonth, $endOfMonth])
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy("status")
                ->pluck("count", "status");

            // Ensure all statuses exist
            $summary = [];
            foreach ($allStatuses as $status) {
                $summary[$status] = $statusCounts[$status] ?? 0;
            }

            // Calculate late percentage
            $lateDays = $summary["Late"] ?? 0;
            $latePercentage = round(($lateDays / $workingDays) * 100, 2);

            // Get first shift
            $shifts = WorkingShift::where("id", $employee->workshift)->first();

            $result[] = [
                "monthYear" => Carbon::now()->format("M-Y"),
                "employee_id" => $employee->id,
                "employee_eid" => $employee->eid,
                "avatar" => $employee->user?->image
                    ? asset("public/" . $employee->user->image)
                    : asset("default-avatar.png"),
                "employee_name" => trim(
                    $employee->fname . " " . $employee->lname
                ),
                "shift" => $shifts->shift_name ?? null,
                "total_days" => $totalDays,
                "summary" => $summary,
                "late_percentage" => $latePercentage,
            ];
        }

        return response()->json($result);
    }





    public function attendanceFilter(Request $request)
    {
        // Use requested month/year or current
        $month = $request->SelectedMonth ?? Carbon::now()->format("m");
        $year = $request->SelectedYear ?? Carbon::now()->format("Y");

        $startOfMonth = Carbon::createFromDate($year, $month, 1)
            ->startOfMonth()
            ->toDateString();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)
            ->endOfMonth()
            ->toDateString();

        $allStatuses = [
            "Holiday",
            "Half",
            "Absent",
            "Present",
            "Leave",
            "Late",
        ];

        // Count holidays in the month
        $holidayCount = Holiday::whereBetween("date", [
            $startOfMonth,
            $endOfMonth,
        ])->count();

        $totalDays = Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $workingDays = max(1, $totalDays - $holidayCount);

        // Load employees with user + shifts
        $employees = Employee::with(["user", "employeeHasShift.shift"])->get([
            "id",
            "fname",
            "lname",
            "eid",
            "user_id",
            "workshift",
        ]);

        $result = [];

        foreach ($employees as $employee) {
            // Attendance summary
            $statusCounts = Attendance::where("employee_id", $employee->id)
                ->whereBetween("date", [$startOfMonth, $endOfMonth])
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy("status")
                ->pluck("count", "status");

            $summary = [];
            foreach ($allStatuses as $status) {
                $summary[$status] = $statusCounts[$status] ?? 0;
            }

            $lateDays = $summary["Late"] ?? 0;
            $latePercentage = round(($lateDays / $workingDays) * 100, 2);

            // Get all shifts assigned to employee
            $shifts = $employee->employeeHasShift
                ->map(fn($s) => $s->shift?->shift_name)
                ->filter()
                ->values();

            $result[] = [
                "monthYear" => Carbon::createFromDate($year, $month, 1)->format(
                    "M-Y"
                ),
                "employee_id" => $employee->id,
                "employee_eid" => $employee->eid,
                "employee_name" => trim(
                    $employee->fname . " " . $employee->lname
                ),
                "avatar" => $employee->user?->image
                    ? asset("public/" . $employee->user->image)
                    : asset("default-avatar.png"),
                "shifts" => $shifts, // array of shift names
                "total_days" => $totalDays,
                "summary" => $summary,
                "late_percentage" => $latePercentage,
            ];
        }

        return response()->json($result);
    }

    // Show specific attendance
    public function show(Attendance $attendance)
    {
        dd("from show attandance");
    }

    //after public Employee attendance
    public function employeeAttendance($id)
    {
        $employee = Employee::with(["department", "workingshift"])->findOrFail(
            $id
        );

        // Determine office start time dynamically
        $officeStart =
            $employee->workingshift && $employee->workingshift->start_time
                ? \Carbon\Carbon::createFromTimeString(
                    $employee->workingshift->start_time
                )
                : \Carbon\Carbon::createFromTimeString("09:00:00");

        $grace = $employee->workingshift->grace_time ?? 0;
        $shift_name = $employee->WorkingShift->shift_name ?? null;

        // Fetch attendances for current month
        $attendances = Attendance::where("employee_id", $id)
            ->whereMonth("date", now()->month)
            ->whereYear("date", now()->year)
            ->orderBy("date", "desc")
            ->get();

        if (count($attendances) <= 0) {
            $nodata = [];
            $nodata[0] = -1;
            $nodata[1] = "Data not found";
            return $nodata;
        }

        $result = $attendances->map(function ($att) use (
            $employee,
            $officeStart
        ) {
            $in = $att->in_time ? \Carbon\Carbon::parse($att->in_time) : null;
            $out = $att->out_time
                ? \Carbon\Carbon::parse($att->out_time)
                : null;

            // Total work minutes
            $production_minutes =
                $in && $out ? $in->diffInMinutes($out) - 60 : 0;

            // Grace time subtraction (optional)
            $grace = $employee->workingshift->grace_time ?? 0;

            // Standard workday in minutes (8 hours)
            $standard_minutes = 8 * 60;

            // Overtime: work beyond standard
            $overtime_minutes = max($production_minutes - $standard_minutes, 0);

            // Convert minutes to hours for convenience
            $production_hours = round($production_minutes / 60, 2);
            $overtime_hours = round($overtime_minutes / 60, 2);

            // Get shift name dynamically
            $shift_name = $employee->workingshift->shift_name ?? null;

            return [
                "id" => $att->id,
                "employee_id" => $att->employee_id,
                "employee_eid" => $employee->eid,
                "employee_name" => trim(
                    $employee->fname . " " . $employee->lname
                ),
                "employee_designation" => $employee->designation,
                "department" => $employee->department->name ?? null,
                "shift" => $shift_name,
                "date" => $att->date,
                "status" => $att->status,
                "in_time" => $att->in_time,
                "out_time" => $att->out_time,
                "late" => $att->late,
                "production_hours" => floor($production_hours * 60),
                "overtime" => floor($overtime_hours * 60),
                "monthYear" => \Carbon\Carbon::parse($att->date)->format("M-Y"),
            ];
        });

        return response()->json($result);
    }

    //Personal attendence sheet for employee id
    public function attendanceFilterPersonal(Request $request, $id)
    {
        $month = $request->SelectedMonth ?? Carbon::now()->format("m");
        $year = $request->SelectedYear ?? Carbon::now()->format("Y");

        $officeStart = Carbon::createFromTimeString("09:00:00");
        $standardMinutes = 8 * 60; // 8 hours standard

        $employee = Employee::with([
            "department",
            "employeehasshift.shift",
        ])->findOrFail($id);

        $attendances = Attendance::where("employee_id", $id)
            ->whereMonth("date", $month)
            ->whereYear("date", $year)
            ->orderBy("date", "desc")
            ->get();

        $result = $attendances->map(function ($att) use (
            $employee,
            $officeStart,
            $standardMinutes
        ) {
            $in = $att->in_time ? Carbon::parse($att->in_time) : null;
            $out = $att->out_time ? Carbon::parse($att->out_time) : null;

            // Production minutes (subtract break if needed)
            $production_minutes =
                $in && $out ? max(0, $in->diffInMinutes($out) - 60) : 0;

            $production_minutes = abs($production_minutes);
            // Overtime hours (above 8 hours)
            $overtime_hours = max(
                0,
                ($production_minutes - $standardMinutes) / 60
            );
            $overtime_hours = floor($overtime_hours);

            // Late minutes
            $late_minutes =
                $in && $in->greaterThan($officeStart)
                    ? $in->diffInMinutes($officeStart)
                    : 0;

            // Shift names
            $shifts = $employee->employeeHasShift
                ->map(fn($s) => $s->shift?->shift_name)
                ->filter()
                ->values();

            return [
                "id" => $att->id,
                "employee_id" => $att->employee_id,
                "employee_eid" => $employee->eid,
                "employee_designation" => $employee->designation,
                "employee_name" => trim(
                    $employee->fname . " " . $employee->lname
                ),
                "department" => $employee->department?->name,
                "shifts" =>
                    WorkingShift::where("id", $employee->workshift)->first()
                        ->shift_name ?? null,
                "date" => $att->date,
                "status" => $att->status,
                "in_time" => $att->in_time,
                "out_time" => $att->out_time,
                "late" => abs($late_minutes),
                "production_minutes" => abs($production_minutes),
                "overtime" => abs($overtime_hours),
                "monthYear" => Carbon::parse($att->date)->format("M-Y"),
            ];
        });

        return response()->json($result);
    }

    //Initial
    // Store attendance with holiday + overtime check
    public function store(Request $request)
    {
        // return $request;
        $request->validate([
            "employee_id" => "required|exists:employees,id",
            "date" => "required|date",
            "in_time" => "nullable|date_format:H:i",
            "out_time" => "nullable|date_format:H:i|after_or_equal:in_time",
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $employeeId = $request->employee_id;

        // Initialize default values
        $status = "Absent";
        $late = 0;
        $production_minutes = 0;
        $overtime_hours = 0;

        $officeStart = Carbon::createFromTime(9, 15); // 09:15 AM (changeable)

        // Check holidays
        $isHoliday = Holiday::where("date", $date)->exists();
        $isPersonalHoliday = PersonalHoliday::where("employee_id", $employeeId)
            ->where("date", $date)
            ->exists();

        if ($request->in_time && $request->out_time) {
            $in = Carbon::createFromFormat("H:i", $request->in_time);
            $out = Carbon::createFromFormat("H:i", $request->out_time);

            // Total worked minutes (exact)
            $production_minutes = $out->diffInMinutes($in);

            // Overtime in hours (only above 8 hours)
            $overtime_hours = max(0, ($production_minutes - 8 * 60) / 60);

            // Late calculation
            $late = 0;
            $officeStart = Carbon::createFromTime(9, 15); // 09:15 AM
            if ($in->greaterThan($officeStart)) {
                $late = $officeStart->diffInMinutes($in);
            }

            // Status
            if (!$isHoliday && !$isPersonalHoliday) {
                $status = $late > 0 ? "Late" : "Present";
            } else {
                $status = "Holiday";
            }
        } else {
            $status = $isHoliday || $isPersonalHoliday ? "Holiday" : "Absent";
            $late = 0;
            $production_minutes = 0;
            $overtime_hours = 0;
        }

        // Save or update attendance
        $attendance = Attendance::updateOrCreate(
            [
                "employee_id" => $employeeId,
                "date" => $date,
            ],
            [
                "in_time" => $request->in_time,
                "out_time" => $request->out_time,
                "status" => $status,
                "late" => $late,
                "production_minutes" => $production_minutes,
                "overtime_hours" => round($overtime_hours, 2),
            ]
        );

        $attendance->load("employee");

        return response()->json($attendance, 201);
    }

    public function bulkStore(Request $request)
    {
        if ($request->has("attendances") && is_array($request->attendances)) {
            $results = [];

            $validEmployeeIds = Employee::pluck("id")->toArray();

            foreach ($request->attendances as $att) {
                if (!isset($att["employee_id"], $att["date"])) {
                    continue;
                }

                if (!in_array($att["employee_id"], $validEmployeeIds)) {
                    // Skip invalid employee
                    continue;
                }

                $results[] = $this->processSingleAttendance($att);
            }

            return response()->json(
                [
                    "message" =>
                        "Bulk attendance synced (only existing employees)",
                    "data" => $results,
                ],
                201
            );
        }

        // Single insert fallback
        $attendance = $this->processSingleAttendance($request->all());
        return response()->json($attendance, 201);
    }

    /**
     * Extracted logic for single record (existing code)
     */
    private function processSingleAttendance(array $att)
    {
        $date = Carbon::parse($att["date"])->toDateString();
        $employeeId = $att["employee_id"];

        $status = "Absent";
        $late = 0;
        $production_minutes = 0;
        $overtime_hours = 0;

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return null; // employee missing
        }

        // Use attendance shift if exists, fallback to employee current shift
        $shiftId = $att["shift"] ?? $employee->workshift;
        $shift = WorkingShift::find($shiftId);

        if (!$shift) {
            // fallback default shift
            $officeStart = Carbon::createFromTime(9, 0);
            $officeEnd = Carbon::createFromTime(17, 0);
            $gracePeriod = 0;
            $standardMinutes = 8 * 60;
        } else {
            // parse shift times
            try {
                $officeStart = !empty($shift->start_time)
                    ? Carbon::parse($shift->start_time)
                    : Carbon::createFromTime(9, 0);
            } catch (\Exception $e) {
                $officeStart = Carbon::createFromTime(9, 0);
            }

            try {
                $officeEnd = !empty($shift->end_time)
                    ? Carbon::parse($shift->end_time)
                    : Carbon::createFromTime(17, 0);
            } catch (\Exception $e) {
                $officeEnd = Carbon::createFromTime(17, 0);
            }

            $gracePeriod = (int) $shift->grace_time;
            $standardMinutes = (int) $shift->working_hours * 60;
        }

        // check holidays
        $isHoliday = Holiday::where("date", $date)->exists();
        $isPersonalHoliday = PersonalHoliday::where("employee_id", $employeeId)
            ->where("date", $date)
            ->exists();

        if (!empty($att["in_time"]) && !empty($att["out_time"])) {
            $in = Carbon::createFromFormat("H:i", $att["in_time"]);
            $out = Carbon::createFromFormat("H:i", $att["out_time"]);

            if ($in->lt($officeStart)) {
                $in = $officeStart->copy();
            }

            $workedMinutes = $in->diffInMinutes($out) - 60; // lunch deduction one hour
            $production_minutes = max(0, $workedMinutes);

            $overtime_hours = max(0, ($workedMinutes - $standardMinutes) / 60);

            if (
                $in->greaterThan($officeStart->copy()->addMinutes($gracePeriod))
            ) {
                $late = $officeStart->diffInMinutes($in);
            }

            $status =
                !$isHoliday && !$isPersonalHoliday
                    ? ($late > 0
                        ? "Late"
                        : "Present")
                    : "Holiday";
        } else {
            $status = $isHoliday || $isPersonalHoliday ? "Holiday" : "Absent";
        }

        return Attendance::updateOrCreate(
            [
                "employee_id" => $employeeId,
                "date" => $date,
            ],
            [
                "in_time" => $att["in_time"] ?? null,
                "out_time" => $att["out_time"] ?? null,
                "status" => $status,
                "shift" => $shiftId,
                "late" => $late,
                "production_minutes" => $production_minutes,
                "overtime_hours" => round($overtime_hours, 2),
            ]
        );
    }

    function makePositive($value)
    {
        return abs($value); // abs() returns absolute value
    }
}
