<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Objection;
use App\Models\Notification;
use App\Models\employee as Employee;
use App\Models\EmployeeHasNotification;

class ObjectionController extends Controller
{
    // Fetch all objections
    public function index()
    {
        $objections = Objection::all();
        return response()->json(
            [
                "status" => "Success",
                "objections" => $objections,
            ],
            200
        );
    }

    // Store a new objection
    public function store(Request $request)
    {
        $request->validate([
            "subject" => "required|string|max:255",
            "objection" => "required|string",
        ]);

        try {
            // Create objection
            $objection = Objection::create([
                "subject" => $request->subject,
                "objection" => $request->objection,
            ]);

            // Create notification for Admins
            $notification = Notification::create([
                "action" => "Objection - " . $objection->subject,
            ]);

            $admins = Employee::whereHas("user", function ($q) {
                $q->where("role", "Admin");
            })->get();

            foreach ($admins as $admin) {
                EmployeeHasNotification::create([
                    "employee_id" => $admin->id,
                    "notification_id" => $notification->id,
                    "type" => "Objection",
                    "is_open" => false, // default unread
                ]);
            }

            return response()->json(
                [
                    "status" => "Success",
                    "message" => "Objection created successfully",
                    "objection" => $objection,
                ],
                201
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Failed to create objection",
                    "error" => $e->getMessage(),
                ],
                500
            );
        }
    }

    // Show a single objection
    public function show($id)
    {
        $objection = Objection::find($id);

        if (!$objection) {
            return response()->json(
                [
                    "status" => "Error",
                    "message" => "Objection not found",
                ],
                404
            );
        }

        return response()->json(
            [
                "status" => "Success",
                "objection" => $objection,
            ],
            200
        );
    }

    // Update an objection (blocked)
    public function update(Request $request, $id)
    {
        return response()->json(
            [
                "status" => "Stopped",
                "message" => "Objections cannot be updated",
            ],
            200
        );
    }

    // Delete an objection (blocked)
    public function destroy($id)
    {
        return response()->json(
            [
                "status" => "Stopped",
                "message" => "Objections cannot be deleted",
            ],
            200
        );
    }
}
