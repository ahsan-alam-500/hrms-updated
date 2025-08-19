<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    // Show all leave applications with employee info
    public function index()
    {
        $leaves = Leave::with('employee')->get();
        return response()->json($leaves);
    }

    // Show specific leave
    public function show(Leave $leave)
    {
        $leave->load('employee');
        return response()->json($leave);
    }

    // Store new leave request
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
        ]);

        $leave = Leave::create($request->all());
        $leave->load('employee');

        return response()->json($leave, 201);
    }

    // Update leave (e.g., for approving/rejecting)
    public function update(Request $request, $id)
    {
        try {
            $data = $request->only(['status', 'employee_id', 'leave_type', 'start_date', 'end_date', 'reason']);

            // $id সরাসরি ব্যবহার করুন, request থেকে নেবেন না
            $leave = Leave::findOrFail($id);
            $leave->update($data);
            $leave->load('employee');

            return response()->json($leave);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Delete leave request (if needed)
    public function destroy(Leave $leave)
    {
        $leave->delete();
        return response()->json(['message' => 'Leave deleted successfully']);
    }
}
