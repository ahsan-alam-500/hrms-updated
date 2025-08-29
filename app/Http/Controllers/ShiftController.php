<?php

namespace App\Http\Controllers\Api;

use App\Models\WorkingShift;
use App\Models\employee as Employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ShiftController extends Controller
{
    //=================x=======================
    // ✅ Get all shifts
    //=================x=======================

    public function index()
    {
        try {
            $shifts = WorkingShift::orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'data'    => $shifts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shifts',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    //==========================x============================
    //✅ Create a shift
    //==========================x============================


    public function store(Request $request)
    {
        $validated = $request->validate([
            'sName'       => 'required|string|max:255',
            'sStartTime'  => 'required|date_format:H:i',
            'sEndTime'    => 'required|date_format:H:i|after:sStartTime',
            'sLateCount'  => 'nullable|integer|min:0',
        ]);

        $shift = WorkingShift::create([
            'shift_name' => $request->sName,
            'start_time' => $request->sStartTime,
            'end_time'   => $request->sEndTime,
            'grace_time' => $request->sLateCount ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $shift
        ], 201);
    }



    //=====================x==============================
    //✅  update a shift
    //=====================x==============================

    public function update(Request $request, $id)
    {
        $shift = WorkingShift::find($id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found'
            ], 404);
        }

        $validated = $request->validate([
            'sName'       => 'sometimes|string|max:255',
            'sStartTime'  => 'sometimes|date_format:H:i',
            'sEndTime'    => 'sometimes|date_format:H:i|after:sStartTime',
            'sLateCount'  => 'nullable|integer|min:0',
        ]);

        $shift->update([
            'shift_name' => $request->sName ?? $shift->shift_name,
            'start_time' => $request->sStartTime ?? $shift->start_time,
            'end_time'   => $request->sEndTime ?? $shift->end_time,
            'grace_time' => $request->sLateCount ?? $shift->grace_time,
        ]);

        return response()->json([
            'success' => true,
            'data'    => $shift
        ]);
    }


    //=======================x=========================
    // ✅ Delete a shift
    //=======================x=========================

    public function destroy($id)
    {
        $shift = WorkingShift::findOrFail($id);

        $shift->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }

    // ==================================================================================================================
    //✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅ A✅
    // ==================================================================================================================


    // ================================x================================
    //✅ Assign shift page opening get method
    // ================================x================================

    public function AssignEmployeeToShiftPage(){
    $data = Employee::with(['user','workingshift'])->get()->map(function($emp){
        return [
            'id' => $emp->id,
            'avatar' => url('public/'.$emp->user->image),
            'fname' => $emp->fname,
            'lname' => $emp->lname,
            'designation' => $emp->designation,
            'eid' => $emp->eid,
            'shift'=>$emp->workingshift,
        ];
    });

    $shifts = WorkingShift::all();

    return response()->json(["data"=>$data,"shifts"=>$shifts]);
    }

    // ================================x=================================
    //✅ Assign ermployee to a shift
    // ================================x=================================

    public function AssignEmployeeToShiftPost(Request $request,$id)
    {


        $employee = Employee::find($id);

        // return $employee;


        $done = $employee->update([
            'workshift' => $request->updateShift,
        ]);


        return response()->json([
            'success' => true
        ],200);
    }


}
