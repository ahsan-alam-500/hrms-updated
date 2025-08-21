<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::all();

        $startDate = Carbon::now()->startOfMonth();
        $endDate   = Carbon::now();

        foreach ($employees as $employee) {
            for ($date = $startDate; $date <= $endDate; $date->addDay()) {

                // Randomize whether employee worked or not
                $hasWorked = rand(0, 1); // 50% chance
                if ($hasWorked) {
                    $inHour  = rand(8, 10);
                    $inMin   = rand(0, 59);
                    $outHour = rand(17, 19);
                    $outMin  = rand(0, 59);

                    $inTime  = Carbon::createFromTime($inHour, $inMin)->format('H:i');
                    $outTime = Carbon::createFromTime($outHour, $outMin)->format('H:i');

                    $in  = Carbon::createFromFormat('H:i', $inTime);
                    $out = Carbon::createFromFormat('H:i', $outTime);

                    $workedHours = $out->diffInMinutes($in) / 60;
                    $overtime = max(0, $workedHours - 8);

                    $officeStart = Carbon::createFromTime(9, 15);
                    $status = $in->greaterThan($officeStart) ? 'Late' : 'Present';
                } else {
                    $inTime = null;
                    $outTime = null;
                    $status = 'Absent';
                    $overtime = 0;
                }

                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date'        => $date->format('Y-m-d'),
                    ],
                    [
                        'in_time'        => $inTime,
                        'out_time'       => $outTime,
                        'status'         => $status,
                        'overtime_hours' => round($overtime, 2),
                    ]
                );
            }
        }

        $this->command->info('Attendance seeded successfully!');
    }
}
