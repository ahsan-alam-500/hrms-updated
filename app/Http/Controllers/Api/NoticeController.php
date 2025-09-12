<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Notice;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\employee;
use App\Models\EmployeeHasNotification;

class NoticeController extends Controller
{
    /**
     * Display all notices
     */
    public function index()
    {
        $notices = Notice::orderBy('id','desc')->get();

        return response()->json($notices, 200);
    }

    /**
     * Store a new notice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "aName" => "required|string|max:255",
            "dis" => "nullable|string",
            "status" => "nullable|in:active,inactive"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errors" => $validator->errors()
            ], 422);
        }

        $notice = Notice::create([
            "title" => $request->aName,
            "description" => $request->dis,
            "status" => $request->status ?? 'inactive' // optional default
        ]);
        
        $notification = Notification::create([
            "action" => "New Notice Published - " . $request->aName   
        ]);
        
        // Assign notification to all employees
        $employees = Employee::all();
        
        foreach ($employees as $employee) {
            EmployeeHasNotification::create([
                "employee_id"    => $employee->id,
                "notification_id"=> $notification->id,
                "type"=> "notice",
                "is_open"        => false // default unread
            ]);
        }
        

        return response()->json([
            "message" => "Notice has been published",
            "notice" => $notice
        ], 201);
    }

    /**
     * Update an existing notice
     */
    public function update(Request $request, $id)
    {
        $notice = Notice::find($id);

        if (!$notice) {
            return response()->json([
                "message" => "Notice not found"
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            "aName" => "sometimes|required|string|max:255",
            "dis" => "nullable|string",
            "status" => "sometimes|required|in:active,inactive"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errors" => $validator->errors()
            ], 422);
        }

        $notice->update([
            "title" => $request->aName ?? $notice->title,
            "description" => $request->dis ?? $notice->description,
            "status" => $request->status ?? $notice->status
        ]);

        return response()->json([
            "message" => "Notice has been updated",
            "notice" => $notice
        ], 200);
    }

    /**
     * Delete a notice
     */
    public function destroy($id)
    {
        $notice = Notice::find($id);

        if (!$notice) {
            return response()->json([
                "message" => "Notice not found"
            ], 404);
        }

        $notice->delete();

        return response()->json([
            "message" => "Notice has been deleted"
        ], 200);
    }
}
