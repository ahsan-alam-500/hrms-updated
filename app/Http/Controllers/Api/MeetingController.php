<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\User;

class MeetingController extends Controller
{
    // 🔹 All meetings with creator user
    public function index()
    {
        $today = now()->toDateString();

        $upcomingMeetings = Meeting::with('user')
            ->whereDate('time', '>=', $today)
            ->orderBy('time', 'asc')
            ->get();

        return response()->json([
            "status"   => true,
            "meetings" => $upcomingMeetings
        ]);
    }


    // 🔹 Store a new meeting
    public function store(Request $request)
    {
        $request->validate([
            "user_id" => "required|exists:users,id",
            "title" => "required|string|max:255",
            "description" => "nullable|string",
            "time" => "required|date",
        ]);

        $meeting = Meeting::create($request->all());

        return response()->json([
            "status" => true,
            "message" => "Meeting created successfully",
            "meeting" => $meeting
        ]);
    }

    // 🔹 Show single meeting
    public function show($id)
    {
        $meeting = Meeting::with('user')->findOrFail($id);

        return response()->json([
            "status" => true,
            "meeting" => $meeting
        ]);
    }

    // 🔹 Update meeting
    public function update(Request $request, $id)
    {
        $meeting = Meeting::findOrFail($id);

        $request->validate([
            "title" => "sometimes|string|max:255",
            "description" => "nullable|string",
            "time" => "sometimes|date",
            "is_done" => "boolean"
        ]);

        $meeting->update($request->all());

        return response()->json([
            "status" => true,
            "message" => "Meeting updated successfully",
            "meeting" => $meeting
        ]);
    }

    // 🔹 Delete meeting
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $meeting->delete();

        return response()->json([
            "status" => true,
            "message" => "Meeting deleted successfully"
        ]);
    }
}
