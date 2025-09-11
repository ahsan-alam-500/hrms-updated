<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\User;
use App\Models\employee as Employee;
use App\Models\department as Department;
use App\Models\leave as Leave;
use App\Models\PersonalHoliday;
use App\Models\holiday as Holiday;
use App\Models\WorkingShift as Shift;
use App\Models\attendance as Attendance;
use App\Models\Target;
use App\Models\Projects as Project;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $month = $today->month;
        $year = $today->year;

        // --- Employee Stats ---
        $allEmployeeIds = Employee::pluck('id');
        $employeeCount = $allEmployeeIds->count();
        $shifts = Shift::count();
        $department = Department::count();

        // --- Holiday & Leave Stats for Today ---
        $personalHolidayEmployees = PersonalHoliday::whereDate('date', $today)
            ->pluck('employee_id');
        $publicHolidayCount = Holiday::whereDate('date', $today)->count();

        $leaveEmployees = Leave::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id');

        $attendanceEmployees = Attendance::whereDate('date', $today)
            ->pluck('employee_id');

        // Merge leave + personal holiday + attendance (not absent)
        $notAbsent = $leaveEmployees
            ->merge($personalHolidayEmployees)
            ->merge($attendanceEmployees)
            ->unique();

        // Present employees today (exclude absent)
        $presentCount = Attendance::whereDate('date', $today)
            ->whereIn('status', ['Present', 'Late'])
            ->count();

        // Absent = all employees - notAbsent
        $absentCount = $allEmployeeIds->diff($notAbsent)->count();

        // Total leave count today (public holidays counted as +1)
        $leaveToday = $publicHolidayCount + $personalHolidayEmployees->count() + $leaveEmployees->count();

        //xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
        // --- Target Stats ---
        //xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;


        //set achieved value first
        $achieved = Project::where('status', 'delivered')->sum('amount');

        // Update target for current month
        Target::where('month', $month)->update(['achieved' => $achieved]);




        $target = (int) Target::where('month', $month)
            ->where('year', $year)
            ->value('target') ?? 0;

        $achievedToday = Project::where('status', 'delivered')
            ->whereDate('updated_at', $today)
            ->sum('amount');

        $achievedMonth = Project::where('status', 'delivered')
            ->whereMonth('updated_at', $month)
            ->whereYear('updated_at', $year)
            ->sum('amount');

        $due = $target - $achievedMonth;
        $percentageAchieved = $target > 0 ? round(($achievedMonth / $target) * 100, 2) : 0;


        $target_card = [
            [
                'Target' => $target
            ],
            [
                'achieved_today' => $achievedToday
            ],
            [
                'achieved_this_month' => $achievedMonth
            ],
            [
                'due' => $due > 0 ? $due : 0
            ],
            [
                'percentage_achieved' => $percentageAchieved . '%'
            ]
        ];




        // --- Response ---
        return response()->json([
            "allemployees"   => $employeeCount,
            "shifts"         => $shifts,
            "department"     => $department,
            "leaveToday"     => $leaveToday,
            "absentToday"    => $absentCount,
            "presentToday"   => $presentCount,
            "target_card"  => $target_card
        ]);
    }
}
