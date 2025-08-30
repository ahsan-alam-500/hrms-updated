<?php

namespace App\Http\Controllers\Api\employee;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\leave;
use Illuminate\Support\Facades\Validator;

class EmployeeDashboard extends Controller
{

    /**********************************
     *****Store a new leave request****
     *********************************/
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type'  => 'required|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $leave = leave::create([
            'employee_id' => $request->employee_id,
            'leave_type'  => $request->leave_type,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'reason'      => $request->reason,
            'status'      => $request->status ?? 'pending'
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'data'    => $leave
        ], 201);
    }

    /**********************************
     ***** Update a leave request******
     *********************************/

    public function update(Request $request, $id)
    {
        $leave = leave::find($id);

        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'leave_type'  => 'sometimes|required|string|max:255',
            'start_date'  => 'sometimes|required|date',
            'end_date'    => 'sometimes|required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
            'status'      => 'in:pending,approved,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $leave->update($request->only([
            'leave_type',
            'start_date',
            'end_date',
            'reason',
            'status'
        ]));

        return response()->json([
            'message' => 'Leave request updated successfully',
            'data'    => $leave
        ], 200);
    }

    /**********************************
     ***** Delete a leave request******
     *********************************/
    public function destroy($id)
    {
        $leave = leave::find($id);

        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        $leave->delete();

        return response()->json([
            'message' => 'Leave request deleted successfully'
        ], 200);
    }
}
