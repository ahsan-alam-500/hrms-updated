<?php

namespace App\Http\Controllers\Api\employee;
use App\Mail\NewLeaveRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\leave;
use App\Models\employee;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\EmployeeHasNotification;

class EmployeeDashboard extends Controller
{
    /**********************************
     ***** Store a new leave request ****
     *********************************/
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'leave_type'  => 'required',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'reason'      => 'nullable|string',
        ]);
        


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate number of days
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDays($end) + 1; // +1 to include start date

        $leave = leave::create([
            'employee_id' => $request->employee_id,
            'leave_type'  => $request->leave_type,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'days'        => $days,
            'reason'      => $request->reason,
            'status'      => $request->status ?? 'pending'
        ]);

        
        $notification = Notification::create([
        "action"=>"Leave Request - ". employee::where("id",$leave->employee_id)->value('fname')
        ]);
        
        $employees = employee::whereHas('user', function($q){
            $q->where('role', 'Admin');
        })->get();
        
        foreach ($employees as $employee) {
            EmployeeHasNotification::create([
                "employee_id"    => $employee->id,
                "notification_id"=> $notification->id,
                "type"        => "leave",
                "is_open"        => false // default unread
            ]);
        }
        

        return response()->json([
            'message' => 'Leave request submitted successfully',
            'data'    => $leave
        ], 201);
    }
    
    public function show($id){
        
        $leaves = leave::where('employee_id',$id)->get();
        
        $count = $leaves->count();

        
        if($count>0){
        return response()->json([
            "leaves"=>$leaves
            ],200);
        }
        
        return response()->json([
        'message' => 'No data found',
        'status' => '403',
        ], 200);
        
        
    }

    /**********************************
     ***** Update a leave request *****
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

        // Recalculate days if dates are updated
        if ($request->has('start_date') || $request->has('end_date')) {
            $start = Carbon::parse($request->start_date ?? $leave->start_date);
            $end = Carbon::parse($request->end_date ?? $leave->end_date);
            $leave->days = $start->diffInDays($end) + 1;
        }

        $leave->update($request->only([
            'leave_type',
            'start_date',
            'end_date',
            'reason',
            'status',
            'days'
        ]));

        return response()->json([
            'message' => 'Leave request updated successfully',
            'data'    => $leave
        ], 200);
    }

    /**********************************
     ***** Delete a leave request ******
     *********************************/
    public function destroy($id)
    {
        $leave = leave::find($id);
        
        if (in_array($leave->status, ['under_review', 'rejected', 'approved'])) {
            return response()->json([
                'status' => 'failed',
                'message' => 'You cannot delete this record now',
            ], 403);
        }

        $leave->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request deleted successfully'
        ], 200);
    }
}
