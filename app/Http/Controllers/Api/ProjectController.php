<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\ProjectHasEmployee;
use App\Models\department as Department;
use App\Models\User;
use App\Models\Projects;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\EmployeeHasNotification;
use Illuminate\Support\Facades\Log;


class ProjectController extends Controller
{

    public function index()
    {
        $projects = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])->orderBy('id', 'desc')->get();

        $projects = $projects->map(function ($project) {
            // --- Project Manager Avatar ---
            $projectManager = $project->projectManager;
            if ($projectManager && $projectManager->user) {
                $projectManager->avatar = url("public/" . $projectManager->user->image);
            }
            $project->project_manager = $projectManager;

            // --- Progress calculation ---
            $progressMap = [
                'To-Do'        => 0,
                'Under Review' => 25,
                'In Progress'  => 50,
                'On Hold'       => 20,
                'Completed'    => 75,
                'Delivered'    => 100,
            ];
            $project->progress = $progressMap[$project->status] ?? 50;

            // --- Taken By (array of employees) ---
            $takenByIds = $project->taken_by ?? [];
            $project->taken_by = !empty($takenByIds)
                ? Employee::whereIn("id", $takenByIds)
                ->get()
                ->map(function ($emp) {
                    return $emp->fname . " " . $emp->lname; // just return name as string
                })
                ->toArray()  // optional, to get plain array of names
                : [];


            // --- Team Leaders ---
            $teamLeaderIds = json_decode($project->team_leader, true) ?? [];
            $project->team_leaders = !empty($teamLeaderIds)
                ? Employee::whereIn("id", $teamLeaderIds)
                ->with("user")
                ->get()
                ->map(function ($leader) {
                    $leader->avatar = $leader->user
                        ? url("public/" . $leader->user->image)
                        : null;
                    return $leader;
                })
                : [];

            // --- Assigned Employees Avatars ---
            $project->employees = $project->assignedEmployees->map(function ($emp) {
                if ($emp->employee && $emp->employee->user) {
                    $emp->employee->avatar = url("public/" . $emp->employee->user->image);
                }
                return $emp->employee;
            });

            unset($project->assignedEmployees);

            return $project;
        });

        return response()->json($projects);
    }


    //=========================================================================
    //Showing catagorised record
    //=========================================================================

    public function groupProject()
    {
        $projects = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])->get();

        // Initialize grouped arrays
        $grouped = [
            'To-Do'        => [],
            'Cancelled'    => [],
            'Under Review' => [],
            'Completed'    => [],
            'Delivered'    => [],
            'Active'       => [],
        ];

        $projects->each(function ($project) use (&$grouped) {

            // --- Project Manager Avatar ---
            $projectManager = $project->projectManager;
            if ($projectManager && $projectManager->user) {
                $projectManager->avatar = url("public/" . $projectManager->user->image);
            }
            $project->project_manager = $projectManager;

            // --- Progress calculation ---
            $progressMap = [
                'To-Do'        => 0,
                'Under Review' => 25,
                'In Progress'  => 50,
                'On Hold'  => 20,
                'Completed'    => 75,
                'Delivered'    => 100,
            ];
            $project->progress = $progressMap[$project->status] ?? 50; // default 50 for other statuses

            // --- Taken By (array of employee names) ---
            $takenByIds = $project->taken_by;
            if (is_string($takenByIds)) {
                $takenByIds = json_decode($takenByIds, true) ?? [];
            }
            $project->taken_by = !empty($takenByIds)
                ? Employee::whereIn("id", $takenByIds)
                ->get()
                ->map(fn($emp) => $emp->fname . " " . $emp->lname)
                ->toArray()
                : [];

            // --- Team Leaders ---
            $teamLeaderIds = $project->team_leader;
            if (is_string($teamLeaderIds)) {
                $teamLeaderIds = json_decode($teamLeaderIds, true) ?? [];
            }
            $project->team_leaders = !empty($teamLeaderIds)
                ? Employee::whereIn("id", $teamLeaderIds)
                ->with("user")
                ->get()
                ->map(function ($leader) {
                    $leader->avatar = $leader->user
                        ? url("public/" . $leader->user->image)
                        : null;
                    return $leader;
                })
                : [];

            // --- Assigned Employees Avatars ---
            $project->employees = $project->assignedEmployees->map(function ($emp) {
                if ($emp->employee && $emp->employee->user) {
                    $emp->employee->avatar = url("public/" . $emp->employee->user->image);
                }
                return $emp->employee;
            });

            unset($project->assignedEmployees);

            // --- Group by status ---
            $status = $project->status;
            if (in_array($status, ['To-Do', 'Cancelled', 'Under Review', 'Completed', 'Delivered'])) {
                $grouped[$status][] = $project;
            } else {
                $grouped['Active'][] = $project;
            }
        });

        return response()->json($grouped);
    }


    //=========================================================================
    //Showing individual record
    //=========================================================================

    public function show($id)
    {
        $project = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])->orderBy('id', 'desc')->find($id);

        if (!$project) {
            return response()->json(["message" => "Project not found"], 404);
        }

        // --- Project Manager Avatar ---
        $projectManager = $project->projectManager;
        if ($projectManager && $projectManager->user) {
            $projectManager->avatar = url("public/" . $projectManager->user->image);
        }
        $project->project_manager = $projectManager;

        // --- Team Leaders ---
        $teamLeaderIds = json_decode($project->team_leader, true) ?? [];
        $project->team_leaders = !empty($teamLeaderIds)
            ? Employee::whereIn("id", $teamLeaderIds)
            ->with("user")
            ->get()
            ->map(function ($leader) {
                $leader->avatar = $leader->user
                    ? url("public/" . $leader->user->image)
                    : null;
                return $leader;
            })
            : [];

        // --- Taken By (array of employees) ---
        // --- Taken By (array of employees) ---
        $takenByIds = $project->taken_by ?? [];

        $project->taken_by = Employee::whereIn('id', $takenByIds)
            ->get()
            ->toArray(); // collection -> array


        // --- Assigned Employees ---
        $project->employees = $project->assignedEmployees->map(function ($emp) {
            if ($emp->employee && $emp->employee->user) {
                $emp->employee->avatar = url("public/" . $emp->employee->user->image);
                $emp->employee->destribution =  $emp->destribution;
            }
            return $emp->employee;
        });

        unset($project->assignedEmployees);

        return response()->json($project);
    }


    //========================================================================
    //Create New Project and assign employees to developer,leader and project manager
    //========================================================================


    public function store(Request $request)
    {
        $validated = $request->validate([
            "project_name"    => "required|string",
            "description"     => "required|string",
            "start_date"      => "required|date",
            "end_date"        => "required|date|after_or_equal:start_date",
            "employee_id"     => "required|integer|exists:employees,id", // project creator
            "team_name"       => "required|string|max:255",
            "team_leader"     => "required|array",
            "team_leader.*"   => "exists:employees,id",
            "client_name"     => "required|string",
            "Department"      => "required|integer",
            "status"          => "required|string",
            "amount"          => "nullable",
            "taken_by"        => "nullable|array",   // ✅ make it array
            "taken_by.*"      => "exists:employees,id",
            "priority"        => "required|string",
            "assign_employee" => "required|array",
            "assign_employee.*" => "exists:employees,id",
        ]);

        $projectData = [
            "name"           => $validated["project_name"],
            "description"    => $validated["description"] ?? null,
            "start_date"     => $validated["start_date"],
            "end_date"       => $validated["end_date"] ?? null,
            "project_manager" => $validated["employee_id"],
            "team_name"      => $validated["team_name"] ?? null,
            "team_leader"    => json_encode($validated["team_leader"]),
            "client"         => $validated["client_name"] ?? null,
            "status"         => $validated["status"] ?? "To-Do",
            "taken_by"       => isset($validated["taken_by"]) ? $validated["taken_by"] : null,
            "amount"         => $validated["amount"] ?? "00",
            "priority"       => $validated["priority"] ?? "Low",
            "Department"     => $validated["Department"],
        ];

        $project = Projects::create($projectData);

        $workPersonAvg = ($project->amount / count($validated["assign_employee"]));

        foreach ($validated["assign_employee"] as $employeeId) {
            ProjectHasEmployee::create([
                "project_id"  => $project->id,
                "employee_id" => $employeeId,
                "destribution" => $workPersonAvg
            ]);



            // set notification
            $notification = Notification::create([
                "action" => "Assigned to a project  - " . $validated["project_name"]
            ]);
            EmployeeHasNotification::create([
                "employee_id"    => $employeeId,
                "notification_id" => $notification->id,
                "type" => "project",
                "is_open"        => false
            ]);
        }


        return response()->json([
            "message" => "Project created and employees assigned successfully",
            "data"    => $project->load("employees"),
        ], 201);
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
            "team_name" => "nullable|string|max:255",
            "team_leader" => "nullable|array",
            "team_leader.*" => "exists:employees,id",
            "client_name" => "nullable|string|max:255",
            "status" => "nullable|string",
            "priority" => "nullable|string",
            "Department" => "nullable|integer",
            "assign_employee" => "required|array",
            "assign_employee.*" => "exists:employees,id",
            "distribution" => "nullable|array",
        ]);

        $project = Projects::findOrFail($id);

        // Update project data
        $projectData = [
            "name" => $validated["project_name"],
            "description" => $validated["description"] ?? $project->description,
            "start_date" => $validated["start_date"],
            "end_date" => $validated["end_date"] ?? $project->end_date,
            "team_name" => $validated["team_name"] ?? $project->team_name,
            "team_leader" => isset($validated["team_leader"]) ? json_encode($validated["team_leader"]) : $project->team_leader,
            "client" => $validated["client_name"] ?? $project->client,
            "status" => $validated["status"] ?? $project->status,
            "priority" => $validated["priority"] ?? $project->priority,
            "Department" => $validated["Department"] ?? $project->Department,
        ];

        $project->update($projectData);

        // Sync assigned employees with distribution
        foreach ($validated["assign_employee"] as $index => $employeeId) {
            $existing = ProjectHasEmployee::where('project_id', $project->id)
                ->where('employee_id', $employeeId)
                ->first();

            ProjectHasEmployee::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'employee_id' => $employeeId
                ],
                [
                    'distribution' => $validated["distribution"][$index]
                        ?? ($existing->distribution ?? 0)
                ]
            );
        }

        return response()->json([
            "message" => "Project updated successfully",
            "data" => $project->load(["employees", "assignedEmployees.employee.user"])
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
    //Unassigned persons
    //========================================================================
    public function notAssigned()
    {
        $projects = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])->get();

        $projects = $projects->map(function ($project) {
            return $project;
        });

        // unassigned employees
        $freeEmployees = Employee::doesntHave('assignedProjects')
            ->with('user')
            ->get()
            ->map(function ($emp) {
                if ($emp->user) {
                    $emp->avatar = $emp->user && $emp->user->image
                        ? url('public/' . $emp->user->image)
                        : "";
                }
                $emp->shift = $emp->workshift ?? "Not Assigned";

                return $emp;
            });

        return response()->json([
            'unassigned_employees' => $freeEmployees
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
