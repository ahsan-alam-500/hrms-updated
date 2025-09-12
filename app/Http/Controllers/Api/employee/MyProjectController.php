<?php

namespace App\Http\Controllers\Api\employee;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Projects;
use App\Models\employee;

class MyProjectController extends Controller
{
    public function index()
    {
        // Auth user কে employee খুঁজে বের করবো
        $employee = employee::where('user_id', Auth::id())->first();

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // Auth employee কোন project এ আছে check করা
        $projects = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])
            ->where(function ($q) use ($employee) {
                // project manager himself
                $q->where("project_manager", $employee->id)
                  // team leader হলে
                  ->orWhereJsonContains("team_leader", $employee->id)
                  // assigned employee হলে
                  ->orWhereHas("assignedEmployees", function ($q2) use ($employee) {
                      $q2->where("employee_id", $employee->id);
                  });
            })
            ->get();
            


        // Transform
        $projects = $projects->map(function ($project) {
            
            $progressMap = [
                'To-Do'        => 0,
                'Under Review' => 25,
                'In Progress'  => 50,
                'Completed'    => 75,
                'Delivered'    => 100,
            ];
            $project->progress = $progressMap[$project->status] ?? 100;
            
            
            // --- Project Manager Avatar ---
            $projectManager = $project->projectManager;
            if ($projectManager && $projectManager->user) {
                $projectManager->avatar = url("public/" . $projectManager->user->image);
            }
            $project->project_manager = $projectManager;

            // --- Team Leaders ---
            if (!empty($project->team_leader)) {
                $teamLeaderIds = json_decode($project->team_leader, true);
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
            

            // --- Employees Avatars ---
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
    
    //==============================================================
    //My project group by status
    //==============================================================
    
    public function myProjectsGrouped()
    {
        $employee = employee::where('user_id', Auth::id())->first();
    
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    
        // Auth employee কোন project এ আছে check করা
        $projects = Projects::with([
            "projectManager.user",
            "assignedEmployees.employee.user",
        ])
        ->where(function ($q) use ($employee) {
            // project manager himself
            $q->where("project_manager", $employee->id)
              // team leader হলে
              ->orWhereJsonContains("team_leader", $employee->id)
              // assigned employee হলে
              ->orWhereHas("assignedEmployees", function ($q2) use ($employee) {
                  $q2->where("employee_id", $employee->id);
              });
        })
        ->get();
    
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
    
            // --- Progress calculation ---
            $progressMap = [
                'To-Do'        => 0,
                'Under Review' => 25,
                'In Progress'  => 50,
                'Completed'    => 75,
                'Delivered'    => 100,
            ];
            $project->progress = $progressMap[$project->status] ?? 50; // default 50 for other statuses
    
            // --- Project Manager Avatar ---
            $projectManager = $project->projectManager;
            if ($projectManager && $projectManager->user) {
                $projectManager->avatar = url("public/" . $projectManager->user->image);
            }
            $project->project_manager = $projectManager;
    
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
            if (in_array($status, ['To-Do','Cancelled','Under Review','Completed','Delivered'])) {
                $grouped[$status][] = $project;
            } else {
                $grouped['Active'][] = $project;
            }
        });
    
        return response()->json($grouped);
    }

}
