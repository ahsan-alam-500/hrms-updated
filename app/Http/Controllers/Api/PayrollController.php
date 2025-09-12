<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\payroll as Payroll;
use App\Models\attendance as Attendance;
use Illuminate\Http\Request;
    use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::with('employee')->get();
        return response()->json($payrolls);
    }

  
    // ========================================calculation ends here =====================================================

        
        public function store(Request $request)
        {
           
            
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'month' => 'required|string',
                'basic_salary' => 'required|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'deductions' => 'nullable|numeric|min:0',
            ]);
        
            $employee_id = $request->employee_id;
            $monthYear = Carbon::parse('01-' . $request->month);
            
            // 1️⃣ Get total working days in the month
            $totalDays = $monthYear->daysInMonth;
        
            // 2️⃣ Get attendance count for this employee in the month
            $attendances = Attendance::where('employee_id', $employee_id)
                ->whereMonth('date', $monthYear->month)
                ->whereYear('date', $monthYear->year)
                ->get();
        
            // Count actual working days (exclude leave/holiday)
            $workingDays = $attendances->whereNotIn('status', ['Leave', 'Holiday'])->count();
        
            // 3️⃣ Calculate pro-rated salary
            $dailySalary = $request->basic_salary / $totalDays;
            $salaryForDaysWorked = $dailySalary * $workingDays;
        
            // 4️⃣ Apply bonus and deductions
            $bonus = $request->bonus ?? 0;
            $deductions = $request->deductions ?? 0;
        
            $net_salary = $salaryForDaysWorked + $bonus - $deductions;
        
            // 5️⃣ Store payroll
            $payroll = Payroll::create([
                'employee_id' => $employee_id,
                'month' => $request->month,
                'basic_salary' => $salaryForDaysWorked,
                'bonus' => $bonus,
                'deductions' => $deductions,
                'net_salary' => $net_salary,
            ]);
        
            return response()->json($payroll, 201);
        }

    // ========================================calculation ends here =====================================================

    // Selected ID Payrolls
    public function show($id)
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        return response()->json($payroll);
    }

    // Update payroll
    public function update(Request $request, $id)
    {
        $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'month' => 'sometimes|string',
            'basic_salary' => 'sometimes|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deductions' => 'nullable|numeric|min:0',
        ]);

        $payroll = Payroll::findOrFail($id);

        $payroll->fill($request->only([
            'employee_id',
            'month',
            'basic_salary',
            'bonus',
            'deductions',
        ]));

        //Update net_salary 
        $basic_salary = $payroll->basic_salary ?? 0;
        $bonus = $payroll->bonus ?? 0;
        $deductions = $payroll->deductions ?? 0;
        $payroll->net_salary = $basic_salary + $bonus - $deductions;

        $payroll->save();

        return response()->json($payroll);
    }

    // Delete Payroll by id
    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();

        return response()->json(['message' => 'Payroll deleted successfully']);
    }
}
