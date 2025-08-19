<?php
namespace App\Http\Controllers\Api;

use App\Models\department as Department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DepartmentController extends Controller
{
    // ✅ Get all departments
    public function index()
    {
        return response()->json(Department::all());
    }

    // ✅ Create new department
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:departments,name',
            'description' => 'nullable|string',
        ]);

        $department = Department::create($request->all());

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department
        ], 201);
    }

    // ✅ Show single department
    public function show(Department $department)
    {
        return response()->json($department);
    }

    // ✅ Update department
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
        ]);

        $department->update($request->all());

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $department
        ]);
    }

    // ✅ Delete department
    public function destroy(Department $department)
    {
        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }
}
