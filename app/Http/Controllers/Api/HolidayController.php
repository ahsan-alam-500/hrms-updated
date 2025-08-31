<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\leave as Leave;
use App\Models\PersonalHoliday;
use App\Models\holiday as Holiday;
use App\Http\Controllers\Controller;

class HolidayController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // Delete past holidays
        Holiday::whereDate('date', '<', $today)->delete();

        // Only upcoming holidays
        $public_holidays = Holiday::whereDate('date', '>=', $today)
            ->orderBy('date', 'asc')
            ->get();

        $employees = Employee::with("personalHolidays")->get();

        return response()->json([
            "employees" => $employees,
            "public"    => $public_holidays,
        ], 200);
    }


    public function store(Request $request)
    {
        $type = $request->hType ?? $request->type;

        if ($type == "1") {
            // personal holiday
            foreach ($request->seletctedEmployee as $employeeId) {
                PersonalHoliday::create([
                    "employee_id" => $employeeId,
                    "name"        => $request->hName,
                    "holidays"    => $request->day,
                ]);
            }

            return response()->json([
                "success" => true,
                "message" => "Personal holidays added successfully.",
            ]);
        } else {
            // public holiday
            $holiday = Holiday::create([
                "name"        => $request->hName,
                "date"        => $request->hDate,
                "description" => $request->dis,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Public holiday created successfully.",
                "data"    => $holiday,
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $type = $request->hType ?? $request->type;

        if ($type == "1") {
            // update personal holiday
            $holiday = PersonalHoliday::findOrFail($id);
            $holiday->update([
                "name"     => $request->hName ?? $holiday->name,
                "holidays" => $request->day ?? $holiday->holidays,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Personal holiday updated successfully.",
                "data"    => $holiday,
            ]);
        } else {
            // update public holiday
            $holiday = Holiday::findOrFail($id);
            $holiday->update([
                "name"        => $request->hName ?? $holiday->name,
                "date"        => $request->hDate ?? $holiday->date,
                "description" => $request->dis ?? $holiday->description,
            ]);

            return response()->json([
                "success" => true,
                "message" => "Public holiday updated successfully.",
                "data"    => $holiday,
            ]);
        }
    }

    public function destroy(Request $request, $id)
    {
        // return response()->json($request->all());
        $type = $request->HolidayType ?? $request->type;

        if ($type == "1") {
            // delete personal holiday
            $holiday = PersonalHoliday::where("employee_id",$id)->first();
            $holiday->delete();

            return response()->json([
                "success" => true,
                "message" => "Personal holiday deleted successfully.",
            ]);
        } else {
            // delete public holiday
            $holiday = Holiday::findOrFail($id);
            $holiday->delete();

            return response()->json([
                "success" => true,
                "message" => "Public holiday deleted successfully.",
            ]);
        }
    }
}
