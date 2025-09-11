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
        //Current Month
        //Current Month
        //Current Month
        $month = Carbon::now()->month;
        $year = Carbon::now()->year;
        $today = Carbon::today();

        // Current month target
        $targetRecord = Target::where('month', $month)
                              ->where('year', $year)
                              ->first();

        $target = $targetRecord ? (float)$targetRecord->target : 0;

        // Achieved today
        $achievedToday = Project::where('status', 'Delivered')
                                ->whereDate('updated_at', $today)
                                ->sum('amount');

        // Achieved this month
        $achievedMonth = Project::where('status', 'Delivered')
                                ->whereMonth('updated_at', $month)
                                ->whereYear('updated_at', $year)
                                ->sum('amount');

        $due = $target - $achievedMonth;
        $percentageAchieved = $target > 0 ? round(($achievedMonth / $target) * 100, 2) : 0;

        //last month
        //last month
        //last month
        $today = Carbon::today();
        $lastMonthDate = Carbon::now()->subMonth();
        $daysInLastMonth = $lastMonthDate->daysInMonth;
        $lastMonth = $lastMonthDate->month;
        $lastMonthYear = $lastMonthDate->year;

        // Last month total
        $achievedLastMonth = Project::where('status', 'Delivered')
                                    ->whereMonth('updated_at', $lastMonth)
                                    ->whereYear('updated_at', $lastMonthYear)
                                    ->sum('amount');

        //Last month revenew this day

        $lastIncomeThisDay = round($achievedLastMonth / $daysInLastMonth,2);

        // Last month average up to this day
        $averageTillTodayLastMonth = $daysInLastMonth > 0
            ? ($achievedLastMonth / $daysInLastMonth) * $today->day
            : 0;

        // Comparison as percentage
        $comparisonPercent = $averageTillTodayLastMonth > 0
            ? round((($achievedToday - $averageTillTodayLastMonth) / $averageTillTodayLastMonth) * 100, 2)
            : ($achievedToday > 0 ? 100 : 0);


        //Monthly sales
        $year = Carbon::now()->year;

        $monthlySales = [];

        for ($m = 1; $m <= 12; $m++) {
            $achieved = Project::where('status', 'Delivered')
                                ->whereMonth('updated_at', $m)
                                ->whereYear('updated_at', $year)
                                ->sum('amount');

            $monthlySales[] = [
                'month' => Carbon::create()->month($m)->format('F'),
                'achieved' => $achieved
            ];
        }


        // Monthly sales vs operation statistics

            $year = Carbon::now()->year;
            $monthlyStats = [];

            for ($m = 1; $m <= 12; $m++) {
                // All projects created in this month
                $projects = Project::whereMonth('created_at', $m)
                                   ->whereYear('created_at', $year)
                                   ->get();

                $totalPlanneds = $projects->sum('amount');
                $totalDelivereds = $projects->where('status', 'Delivered')->sum('amount');
                $due = $totalPlanneds - $totalDelivereds;
                $percentageAchieveds = $totalPlanneds > 0
                    ? round(($totalDelivereds / $totalPlanneds) * 100, 2)
                    : 0;

                $monthlyStats[] = [
                    'month' => Carbon::create()->month($m)->format('F'),
                    'total_projects' => $projects->count(),
                    'sales' => $totalPlanneds,
                    'revenue' => $totalDelivereds,
                    'due' => $due > 0 ? $due : 0,
                    'percentage_achieved' => $percentageAchieveds . '%'
                ];
            }


    //Absent last day
    $absentLastDay = Attendance::where('status','Absent')->get();


        // --- Response ---
        // --- Response ---
        // --- Response ---
        // --- Response ---
        // --- Response ---
        return response()->json([
            "allemployees"   => $employeeCount,
            "shifts"         => $shifts,
            "department"     => $department,
            "leaveToday"     => $leaveToday,
                    "target_card"  => [
                    'Target' => $target,
                    'achieved_today' => $achievedToday,
                    'achieved_this_month' => $achievedMonth,
                    'due' => $due > 0 ? $due : 0,
                    'percentage_achieved' => round($percentageAchieved,2),
                    'comparison' => ceil($comparisonPercent > 0 ? "+".$comparisonPercent : $comparisonPercent )." %",
                    'achieved_last_month'=>$achievedLastMonth,
                    'lastIncomeThisDay'=>round($lastIncomeThisDay,2)
                    ],
            'monthly_sales' => $monthlySales,
            'monthly_statistics' => $monthlyStats,
            'absent_last_day'=>$absentLastDay
        ]);
    }
}
