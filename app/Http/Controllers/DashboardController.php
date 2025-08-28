<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\employee as Employee;
use App\Models\department as Department;
use App\Models\leave as Leave;
use App\Models\PersonalHoliday;
use App\Models\holiday as Holiday;
use App\Models\WorkingShift as Shift;
use App\Models\attendance as Attendance;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $allEmployees = Employee::pluck('id'); // only ids for calculation
        $employeeCount = $allEmployees->count();

        $shifts = Shift::count();
        $department = Department::count();

        // today's holidays
        $personalHolidayEmployees = PersonalHoliday::whereDate('date', today())
                                ->pluck('employee_id');
        $publicHoliday = Holiday::whereDate('date', today())->count();

        // today's leave employees
        $leaveEmployees = Leave::whereDate('start_date', '<=', today())
                               ->whereDate('end_date', '>=', today())
                               ->pluck('employee_id');

        // today's attendance employees
        $attendanceEmployees = Attendance::whereDate('date', today())
                                         ->pluck('employee_id');

        // merge leave + personal holiday + attendance (not absent)
        $notAbsent = $leaveEmployees
                        ->merge($personalHolidayEmployees)
                        ->merge($attendanceEmployees)
                        ->unique();
        //present
        $presentCount = Attendance::whereDate('date', today())
                          ->whereIn('status', ['Present', 'Late','absent']) // only present/late are counted
                          ->count();

        // absent = all employees - notAbsent
        $absentCount = $allEmployees->diff($notAbsent)->count();

        // total leave count (public holiday is just +1, not employee based)
        $leaveToday = $publicHoliday + $personalHolidayEmployees->count() + $leaveEmployees->count();

        return response()->json([
            "allemployees" => $employeeCount,
            "shifts"       => $shifts,
            "department"   => $department,
            "leaveToday"   => $leaveToday,
            "absentToday"  => $absentCount,
            "presentToday"  => $presentCount,
        ]);
    }
}
