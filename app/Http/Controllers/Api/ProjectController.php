<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\ProjectHasEmployee;
use App\Models\department as Department;
use App\Models\Projects;
use Illuminate\Support\Facades\DB;


class ProjectController extends Controller
{
    public function index()
    {
        // Eager load project manager and assigned employees
        $projects = Projects::with([
            "projectManager.user", // project manager and their user
            "assignedEmployees.employee.user", // assigned employees and their users
        ])->get();

        // Transform data for API response
        $projects = $projects->map(function ($project) {
            // --- Project Manager Avatar ---
            $projectManager = $project->projectManager;
            if ($projectManager && $projectManager->user) {
                $projectManager->avatar = url(
                    "public/" . $projectManager->user->image
                );
            }
            $project->project_manager = $projectManager;

            // --- Team Leaders ---
            if (!empty($project->team_leader)) {
                $teamLeaderIds = json_decode($project->team_leader); // stored as JSON array
                $project->team_leaders = employee::whereIn("id", $teamLeaderIds)
                    ->with("user")
                    ->get()
                    ->map(function ($leader) {
                        $leader->avatar = $leader->user
                            ? url("public/" . $leader->user->image)
                            : null;
                        return $leader;
                    });
            } else {
                $project->team_leaders = [];
            }

            // --- Assigned Employees Avatars ---
            $project->employees = $project->assignedEmployees->map(function (
                $emp
            ) {
                if ($emp->employee && $emp->employee->user) {
                    $emp->employee->avatar = url(
                        "public/" . $emp->employee->user->image
                    );
                }
                return $emp->employee;
            });

            // Remove intermediate relation
            unset($project->assignedEmployees);

            return $project;
        });

        return response()->json($projects);
    }

    //=========================================================================
    //Showing individual record
    //=========================================================================

    public function show($id)
    {
        // Eager load project manager and assigned employees
        $project = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])->find($id);

        if (!$project) {
            return response()->json(["message" => "Project not found"], 404);
        }

        // --- Project Manager Avatar ---
        $projectManager = $project->projectManager;
        if ($projectManager && $projectManager->user) {
            $projectManager->avatar = url(
                "public/" . $projectManager->user->image
            );
        }
        $project->project_manager = $projectManager;

        // --- Team Leaders ---
        if (!empty($project->team_leader)) {
            $teamLeaderIds = json_decode($project->team_leader);
            $project->team_leaders = employee::whereIn("id", $teamLeaderIds)
                ->with("user")
                ->get()
                ->map(function ($leader) {
                    $leader->avatar = $leader->user
                        ? url("public/" . $leader->user->image)
                        : null;
                    return $leader;
                });
        } else {
            $project->team_leaders = [];
        }

        // --- Assigned Employees Avatars ---
        $project->employees = $project->assignedEmployees->map(function ($emp) {
            if ($emp->employee && $emp->employee->user) {
                $emp->employee->avatar = url(
                    "public/" . $emp->employee->user->image
                );
            }
            return $emp->employee;
        });

        unset($project->assignedEmployees); // Remove intermediate relation

        return response()->json($project);
    }

    //========================================================================
    //Create New Project and assign employees to developer,leader and project manager
    //========================================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            "project_name" => "required|string",
            "description" => "required|string",
            "start_date" => "required|date",
            "end_date" => "required|date|after_or_equal:start_date",
            "employee_id" => "required|integer|exists:employees,id", // project creator
            "team_name" => "required|string|max:255",
            "team_leader" => "required|array", // array of employee IDs
            "team_leader.*" => "exists:employees,id",
            "client_name" => "required|string",
            "Department" => "required|integer",
            "status" => "required|string",
            "priority" => "required|string",
            "assign_employee" => "required|array",
            "assign_employee.*" => "exists:employees,id",
        ]);

        // Map fields to DB structure, store team_leader as JSON
        $projectData = [
            "name" => $validated["project_name"],
            "description" => $validated["description"] ?? null,
            "start_date" => $validated["start_date"],
            "end_date" => $validated["end_date"] ?? null,
            "project_manager" => $validated["employee_id"],
            "team_name" => $validated["team_name"] ?? null,
            "team_leader" => json_encode($validated["team_leader"]), // store as JSON
            "client" => $validated["client_name"] ?? null,
            "status" => $validated["status"] ?? "To-Do",
            "priority" => $validated["priority"] ?? "Low",
            "Department" => $validated["Department"],
        ];

        // Create project
        $project = Projects::create($projectData);

        // Assign employees to project
        foreach ($validated["assign_employee"] as $employeeId) {
            ProjectHasEmployee::create([
                "project_id" => $project->id,
                "employee_id" => $employeeId,
            ]);
        }

        return response()->json(
            [
                "message" =>
                "Project created and employees assigned successfully",
                "data" => $project->load("employees"),
            ],
            201
        );
    }

    //========================================================================
    //Updating individual record
    //========================================================================
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            "project_name" => "required|string|max:255",
            "description" => "nullable|string",
            "start_date" => "required|date",
            "end_date" => "nullable|date|after_or_equal:start_date",
            "employee_id" => "required|integer|exists:employees,id", // project manager
            "team_name" => "nullable|string|max:255",
            "team_leader" => "nullable|array", // array of employee IDs
            "team_leader.*" => "exists:employees,id",
            "client_name" => "nullable|string|max:255",
            "status" => "nullable|string",
            "priority" => "nullable|string",
            "Department" => "nullable|integer",
            "assign_employee" => "required|array",
            "assign_employee.*" => "exists:employees,id",
        ]);

        $project = Projects::findOrFail($id);

        // Map fields like store method
        $projectData = [
            "name" => $validated["project_name"],
            "description" => $validated["description"] ?? $project->description,
            "start_date" => $validated["start_date"],
            "end_date" => $validated["end_date"] ?? $project->end_date,
            "project_manager" => $validated["employee_id"],
            "team_name" => $validated["team_name"] ?? $project->team_name,
            "team_leader" => isset($validated["team_leader"]) ? json_encode($validated["team_leader"]) : $project->team_leader,
            "client" => $validated["client_name"] ?? $project->client,
            "status" => $validated["status"] ?? $project->status,
            "priority" => $validated["priority"] ?? $project->priority,
            "Department" => $validated["Department"] ?? $project->Department,
        ];

        $project->update($projectData);

        // Sync assigned employees
        ProjectHasEmployee::where("project_id", $project->id)->delete();
        foreach ($validated["assign_employee"] as $employeeId) {
            ProjectHasEmployee::create([
                "project_id" => $project->id,
                "employee_id" => $employeeId,
            ]);
        }

        return response()->json([
            "message" => "Project updated successfully",
            "data" => $project->load("employees"), // Ensure relation exists
        ]);
    }


    //========================================================================
    //Delete individual record
    //========================================================================
    public function destroy($id)
    {
        $project = Projects::findOrFail($id);

        // Transaction use for safety
        DB::transaction(function () use ($project) {
            // Delete assigned employees
            ProjectHasEmployee::where("project_id", $project->id)->delete();

            // Delete the project
            $project->delete();
        });

        return response()->json([
            "message" => "Project deleted successfully"
        ]);
    }


    //========================================================================
    //Project Attributes for fetching data to fronend
    //========================================================================
    public function attributes()
    {
        $employees = Employee::with("department")->get();
        $departments = Department::all();
        return response()->json(
            [
                "developers" => $employees,
                "departments" => $departments,
            ],
            200
        );
    }
}
