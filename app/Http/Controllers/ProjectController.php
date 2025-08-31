<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Projects;

class ProjectController extends Controller
{

    public function index()
    {
        $projects = Projects::with('projectsincentives')->get();
        return response()->json($projects);
    }


    public function show($id)
    {
        $project = Projects::with('projectsincentives')->find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        return response()->json($project);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'required|string|in:pending,ongoing,completed,cancelled',
            'start_date'     => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'project_manager'=> 'required|string|max:255',
            'client'         => 'nullable|string|max:255',
        ]);

        $project = Projects::create($validated);

        return response()->json([
            'message' => 'Project created successfully',
            'data'    => $project
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $project = Projects::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->update($request->only(array_keys($request->all())));

        return response()->json([
            'message' => 'Project updated successfully',
            'data'    => $project
        ]);
    }


    public function destroy($id)
    {
        $project = Projects::find($id);

        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
