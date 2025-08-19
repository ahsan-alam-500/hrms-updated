<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\department;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{

    public function index()
    {
        $employees = Employee::with('department','user')->get();

        $employees->transform(function ($employee) {
            $employee->avatar = $employee->user && $employee->user->image
                ? url('public/'.$employee->user->image)
                : null;
            return $employee;
        });

        return response()->json([
            'employees' => $employees
        ]);
    }



    public function store(Request $request)
    {


        // Step 1: Validate fields
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'emplyeetype' => 'nullable|string',
            'role' => 'required|string',
            'dob' => 'nullable',
            'salary' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|mimes:jpg,jpeg,webp,png',
            'image_url' => 'nullable|url',
            'image_base64' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePath = null;

        // Case 1: Local file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);
            $imagePath = 'images/' . $filename;
        }

        // Case 2: Base64 upload
        elseif ($request->image_base64) {
            $imageData = $request->image_base64;
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $filename = time() . '.png';
            file_put_contents(public_path('images/') . $filename, base64_decode($imageData));
            $imagePath = 'images/' . $filename;
        }

        // Case 3: External image URL
        elseif ($request->image_url) {
            $imageUrl = $request->image_url;
            $fileContents = @file_get_contents($imageUrl);

            if ($fileContents !== false) {
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (!$extension) $extension = 'jpg';
                $filename = time() . '.' . $extension;
                file_put_contents(public_path('images/') . $filename, $fileContents);
                $imagePath = 'images/' . $filename;
            }
        }

        // Step 2: Check if user exists
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            $user = $existingUser;
        } else {
            if (!$request->password) {
                return response()->json(['error' => 'Password is required for new user'], 422);
            }

            $user = User::create([
                'name' => $request->fname . ' ' . $request->lname,
                'email' => $request->email,
                'image' => $imagePath,
                'password' => bcrypt($request->password),
            ]);
        }

        // Step 3: Create employee
        $employee = Employee::create([
            'user_id' => $user->id,
            'emplyeetype' => $request->emplyeetype,
            'eid' => 'SIT-' . substr(time() . rand(100, 999), -6),
            'fname' => $request->fname,
            'lname' => $request->lname,
            'gender' => $request->gender,
            'nationalid' => $request->nationalid,
            'dob' => $request->dob,
            'level' => $request->level,
            'meritalstatus' => $request->meritalstatus,
            'email' => $request->email,
            'phone' => $request->phone,
            'emergencycontactname' => $request->emergencycontactname,
            'emergencycontactphone' => $request->emergencycontactphone,
            'address' => $request->address,
            'designation' => $request->designation,
            'department_id' => $request->department_id,
            'joindate' => $request->joindate,
            'probitionprioed' => $request->probitionprioed,
            'reportingmanager' => $request->reportingmanager,
            'workshift' => $request->workshift,
            'salary' => $request->salary ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => $existingUser
                ? 'Employee added to existing user successfully'
                : 'Employee & User created successfully',
            'user' => $user,
            'avatar'=>url($user->image),
            'employee' => $employee,
            'response' => 'success',
            'response_code' => '200'
        ], 201);
}




    // GET /employees/{id}
    public function show($id)
    {
        $employee = Employee::with('department','user')->find($id);

        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->avatar = $employee->user && $employee->user->image
                ? url('public/'.$employee->user->image)
                : null;

        return response()->json([
            'status' => 'success',
            'employee' => $employee,
        ], 200);
    }

    // PUT/PATCH /employees/{id}
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->update($request->all());

        return response()->json(['message' => 'Employee updated', 'data' => $employee]);
    }

    // DELETE /employees/{id}
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted']);
    }
}
