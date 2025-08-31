<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\Team;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function index()
    {
        try {
            $teams = Team::with('teamEmployees.employee.user')->get();

            $teams->transform(function ($team) {
                // Fetch leader info
                $leader = \App\Models\Employee::with('user')->find($team->team_leader);
                $team->team_leader = $leader ? [
                    'id' => $leader->id,
                    'name' => $leader->fname . ' ' . $leader->lname,
                    'avatar' => $leader->user ? url('public/' . $leader->user->image) : null
                ] : null;

                // Map team_employees to just employees
                $team->team_employees = $team->teamEmployees->map(function ($te) {
                    if (!$te->employee) return null;
                    $employee = $te->employee;
                    $employee->avatar = $employee->user ? url('public/' . $employee->user->image) : null;
                    return $employee;
                })->filter(); // remove nulls

                // Remove old teamEmployees relationship
                unset($team->teamEmployees);

                return $team;
            });

            return response()->json([
                'status' => 'success',
                'teams' => $teams
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    //======================================================
    //New Team Creation
    //======================================================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'team_name'   => 'required|string|max:100',
            'team_leader' => 'nullable|exists:employees,id',
            'team_formed' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $team = Team::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'data' => $team
        ], 201);
    }

    //======================================================
    //Show a individual team details
    //======================================================
    public function show($id)
    {
        $team = Team::with('teamEmployees.employee')->find($id);

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $team
        ]);
    }

    //======================================================
    //updating existing teams (one by one)
    //======================================================
    public function update(Request $request, $id)
    {
        $team = Team::find($id);

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'team_name'   => 'sometimes|required|string|max:100',
            'team_leader' => 'nullable|exists:employees,id',
            'team_formed' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $team->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Team updated successfully',
            'data' => $team
        ]);
    }


    //======================================================
    // Delete a exact team with individual ID
    //======================================================
    public function destroy($id)
    {
        $team = Team::find($id);

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team not found'
            ], 404);
        }

        $team->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Team deleted successfully'
        ]);
    }
}
