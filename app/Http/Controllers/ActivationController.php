<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

class ActivationController extends Controller
{
    public function activate(Request $request, $id)
    {
        // record খুঁজে বের করা
        $attendance = Attendance::where('employee_id', $id)
            ->where('date', $request->date)
            ->first();

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record not found',
            ], 404);
        }

        // শুধু যেগুলো আসবে সেগুলো update হবে
        $attendance->update($request->only(array_keys($request->all())));

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully',
            'data' => $attendance
        ]);
    }
}
