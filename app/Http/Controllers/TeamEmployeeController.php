<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeamHasEmployee;
use Illuminate\Support\Facades\Validator;

class TeamEmployeeController extends Controller
{
    public function index()
    {
        $teamEmployees = TeamHasEmployee::with(['team', 'employee'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $teamEmployees
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_id'       => 'required|exists:project_teams,id',
            'employee_ids'  => 'required|array',
            'employee_ids.*'=> 'exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [];
        foreach ($request->employee_ids as $employee_id) {
            $data[] = [
                'team_id'     => $request->team_id,
                'employee_id' => $employee_id
            ];
        }

        // Insert multiple records at once
        $teamEmployees = TeamHasEmployee::insert($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Employees assigned to team successfully',
            'assigned_count' => count($data)
        ], 201);
    }

    public function show($id)
    {
        $teamEmployee = TeamHasEmployee::with(['team', 'employee'])->find($id);

        if (!$teamEmployee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $teamEmployee
        ]);
    }

    public function update(Request $request, $id)
    {
        $teamEmployee = TeamHasEmployee::find($id);

        if (!$teamEmployee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'team_id'     => 'sometimes|required|exists:project_teams,id',
            'employee_id' => 'sometimes|required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $teamEmployee->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Record updated successfully',
            'data' => $teamEmployee
        ]);
    }

    public function destroy($id)
    {
        $teamEmployee = TeamHasEmployee::find($id);

        if (!$teamEmployee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        $teamEmployee->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully'
        ]);
    }
}
