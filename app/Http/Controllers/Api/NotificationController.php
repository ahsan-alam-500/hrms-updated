<?php

namespace App\Http\Controllers\Api;

use App\Models\EmployeeHasNotification;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    // ✅ Get all notifications for logged-in employee
    public function index(Request $request)
    {
        $employeeId = $request->user()->employee->id;

        $notifications = EmployeeHasNotification::with('notification')
            ->where('employee_id', $employeeId)
            ->orderBy('id', 'desc')
            ->get();
        $unread = EmployeeHasNotification::with('notification')
            ->where('employee_id', $employeeId)->where('is_open', 0)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            "notifications" => $notifications,
            "unread" => $unread->count()
        ]);
    }

    // ✅ Mark a notification as read
    public function markread($id, Request $request)
    {

        // Find pivot record
        $pivot = EmployeeHasNotification::where('employee_id', $request->employee_id)
            ->where('id', $id)
            ->first();

        if (!$pivot) {
            return response()->json(["message" => "Notification not found for this employee"], 404);
        }

        // Mark as read only if not already read
        if (!$pivot->is_open) {
            $pivot->is_open = true;
            $pivot->save();
        }

        return response()->json(["message" => "Notification marked as read"]);
    }


    // ✅ Delete notification for this employee
    public function destroy($id, Request $request)
    {
        $employeeId = $request->user()->employee->id;

        $pivot = EmployeeHasNotification::where('employee_id', $employeeId)
            ->where('notification_id', $id)
            ->first();

        if (!$pivot) {
            return response()->json(["message" => "Notification not found"], 404);
        }

        $pivot->delete();

        return response()->json(["message" => "Notification deleted"]);
    }
}
