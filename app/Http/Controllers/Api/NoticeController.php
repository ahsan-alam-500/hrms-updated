<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Notice;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class NoticeController extends Controller
{
    /**
     * Display all notices
     */
    public function index()
    {

        $notices = Notice::all();

        return response()->json($notices, 200);
    }

    /**
     * Store a new notice
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "title"       => "required|string|max:255",
            "description" => "nullable|string",
            "status"      => "required|in:active,inactive"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errors" => $validator->errors()
            ], 422);
        }

        $notice = Notice::create($validator->validated());

        return response()->json([
            "message" => "Notice has been published",
            "notice"  => $notice
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
            "title"       => "sometimes|required|string|max:255",
            "description" => "nullable|string",
            "status"      => "sometimes|required|in:active,inactive"
        ]);

        if ($validator->fails()) {
            return response()->json([
                "errors" => $validator->errors()
            ], 422);
        }

        $notice->update($validator->validated());

        return response()->json([
            "message" => "Notice has been updated",
            "notice"  => $notice
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
