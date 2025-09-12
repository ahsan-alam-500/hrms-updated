<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\employee as Employee;
use App\Models\department;
use App\Models\WorkingShift;
use App\Models\EmployeeHasShift;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class EmployeeController extends Controller
{
    
    //===========================================================
    //============== Get All Employees ==========================
    //===========================================================
    public function index()
    {
        $employees = Employee::with("department", "user")
            ->orderBy("id", "desc")
            ->get();

        $employees->transform(function ($employee) {
            $employee->avatar =
                $employee->user && $employee->user->image
                    ? url("public/" . $employee->user->image)
                    : null;
            return $employee;
        });

        return response()->json([
            "employees" => $employees,
        ]);
    }


    //===========================================================
    //============== Store New Employee =========================
    // Handles:
    //  - User creation (if not exists)
    //  - Image upload (file, base64, URL)
    //  - Employee creation linked to User
    //===========================================================
    
    public function store(Request $request)
    {
        // Step 1: Validate fields
        $validator = Validator::make($request->all(), [
            "fname" => "required|string|max:255",
            "lname" => "required|string|max:255",
            "email" => "required|email",
            "password" => "required|min:6",
            "department_id" => "nullable|exists:departments,id",
            "emplyeetype" => "nullable|string",
            "role" => "required|string",
            "dob" => "nullable",
            "salary" => "nullable|numeric|min:0",
            "image" => "nullable|file|mimes:jpg,jpeg,webp,png",
            "image_url" => "nullable|url",
            "image_base64" => "nullable|string",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $imagePath = null;

        // Case 1: Local file upload
        if ($request->hasFile("image")) {
            $file = $request->file("image");
            $filename = time() . "." . $file->getClientOriginalExtension();
            $file->move(public_path("images"), $filename);
            $imagePath = "images/" . $filename;
        }

        // Case 2: Base64 upload
        elseif ($request->image_base64) {
            $imageData = $request->image_base64;
            $imageData = preg_replace(
                "/^data:image\/\w+;base64,/",
                "",
                $imageData
            );
            $imageData = str_replace(" ", "+", $imageData);
            $filename = time() . ".png";
            file_put_contents(
                public_path("images/") . $filename,
                base64_decode($imageData)
            );
            $imagePath = "images/" . $filename;
        }

        // Case 3: External image URL
        elseif ($request->image_url) {
            $imageUrl = $request->image_url;
            $fileContents = @file_get_contents($imageUrl);

            if ($fileContents !== false) {
                $extension = pathinfo(
                    parse_url($imageUrl, PHP_URL_PATH),
                    PATHINFO_EXTENSION
                );
                if (!$extension) {
                    $extension = "jpg";
                }
                $filename = time() . "." . $extension;
                file_put_contents(
                    public_path("images/") . $filename,
                    $fileContents
                );
                $imagePath = "images/" . $filename;
            }
        }

        // Step 2: Check if user exists
        $existingUser = User::where("email", $request->email)->first();

        if ($existingUser) {
            $user = $existingUser;
        } else {
            if (!$request->password) {
                return response()->json(
                    ["error" => "Password is required for new user"],
                    422
                );
            }

            $user = User::create([
                "fname" => $request->fname,
                "lname" => $request->lname,
                "name" => $request->fname . " " . $request->lname,
                "email" => $request->email,
                "image" => $imagePath,
                "password" => bcrypt($request->password),
            ]);
        }

        // Step 3: Create employee
        $employee = Employee::create([
            // "id" => $request->id ?? "",
            "user_id" => $user->id,
            "emplyeetype" => $request->emplyeetype,
            "eid" => "SIT-" . substr(time() . rand(100, 999), -6),
            "fname" => $request->fname,
            "lname" => $request->lname,
            "gender" => $request->gender,
            "nationalid" => $request->nationalid,
            "dob" => $request->dob,
            "level" => $request->level,
            "meritalstatus" => $request->meritalstatus,
            "email" => $request->email,
            "phone" => $request->phone,
            "emergencycontactname" => $request->emergencycontactname,
            "emergencycontactphone" => $request->emergencycontactphone,
            "address" => $request->address,
            "designation" => $request->designation,
            "department_id" => $request->department_id,
            "joindate" => $request->joindate,
            "probitionprioed" => $request->probitionprioed,
            "reportingmanager" => $request->reportingmanager,
            "workshift" => $request->workshift,
            "salary" => $request->salary ?? 0,
            "status" => $request->status ?? "active",
        ]);

        return response()->json(
            [
                "message" => $existingUser
                    ? "Employee added to existing user successfully"
                    : "Employee & User created successfully",
                "user" => $user,
                "avatar" => url($user->image),
                "employee" => $employee,
                "response" => "success",
                "response_code" => "200",
            ],
            201
        );
    }

    //===========================================================
    //============== Get Single Employee ========================
    // Route: GET /employees/{id}
    //===========================================================
    public function show($id)
    {
        $employee = Employee::with("department", "user")->find($id);

        if (!$employee) {
            return response()->json(["message" => "Employee not found"], 404);
        }

        $employee->avatar =
            $employee->user && $employee->user->image
                ? url("public/" . $employee->user->image)
                : null;

        return response()->json(
            [
                "status" => "success",
                "employee" => $employee,
            ],
            200
        );
    }

    //===========================================================
    //========== Update Employee & User Together ================
    // Handles:
    //  - User update (name, email, password, image)
    //  - Employee update (profile, department, salary, etc.)
    //===========================================================
    
    public function update(Request $request, $id)
    {
        // Step 1: Validate fields
        $validator = Validator::make($request->all(), [
            "fname" => "required|string|max:255",
            "lname" => "required|string|max:255",
            "email" => "required|email",
            "password" => "nullable|min:6",
            "department_id" => "nullable|exists:departments,id",
            "emplyeetype" => "nullable|string",
            "role" => "nullable|string",
            "dob" => "nullable",
            "salary" => "nullable|numeric|min:0",
            "image" => "nullable|file|mimes:jpg,jpeg,webp,png",
            "image_url" => "nullable|url",
            "image_base64" => "nullable|string",
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        // Step 2: Find Employee
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(["message" => "Employee not found"], 404);
        }

        $user = $employee->user;

        // Step 3: Handle image
        $imagePath = $user->image; // keep existing by default

        if ($request->hasFile("image")) {
            $file = $request->file("image");
            $filename = time() . "." . $file->getClientOriginalExtension();
            $file->move(public_path("images"), $filename);
            $imagePath = "images/" . $filename;
        } elseif ($request->image_base64) {
            $imageData = preg_replace(
                "/^data:image\/\w+;base64,/",
                "",
                $request->image_base64
            );
            $imageData = str_replace(" ", "+", $imageData);
            $filename = time() . ".png";
            file_put_contents(
                public_path("images/") . $filename,
                base64_decode($imageData)
            );
            $imagePath = "images/" . $filename;
        } elseif ($request->image_url) {
            $fileContents = @file_get_contents($request->image_url);
            if ($fileContents !== false) {
                $extension = pathinfo(
                    parse_url($request->image_url, PHP_URL_PATH),
                    PATHINFO_EXTENSION
                );
                if (!$extension) {
                    $extension = "jpg";
                }
                $filename = time() . "." . $extension;
                file_put_contents(
                    public_path("images/") . $filename,
                    $fileContents
                );
                $imagePath = "images/" . $filename;
            }
        }

        // Step 4: Update User
        $user->update([
            "lname" => $request->lname,
            "fname" => $request->fname,
            "name" => $request->fname . " " . $request->lname,
            "email" => $request->email,
            "role" => $request->role,
            "password" => $request->password
                ? bcrypt($request->password)
                : $user->password,
            "image" => $imagePath,
        ]);

        // Step 5: Update Employee
        $employee = $employee->update([
            "fname" => $request->fname,
            "lname" => $request->lname,
            "emplyeetype" => $request->emplyeetype,
            "gender" => $request->gender,
            "nationalid" => $request->nationalid,
            "dob" => $request->dob,
            "level" => $request->level,
            "meritalstatus" => $request->meritalstatus,
            "email" => $request->email,
            "phone" => $request->phone,
            "emergencycontactname" => $request->emergencycontactname,
            "emergencycontactphone" => $request->emergencycontactphone,
            "address" => $request->address,
            "designation" => $request->designation,
            "department_id" => $request->department_id,
            "joindate" => $request->joindate,
            "probitionprioed" => $request->probitionprioed,
            "reportingmanager" => $request->reportingmanager,
            "salary" => $request->salary ?? $employee->salary,
            "workshift" => $request->workshift,
            "status" => $request->status ?? $employee->status,
        ]);

        return response()->json(
            [
                "message" => "Employee & User updated successfully",
                "user" => $user,
                "avatar" => url($user->image),
                "employee" => $employee,
                "response" => "success",
                "response_code" => "200",
            ],
            200
        );
    }

    //===========================================================
    //============== Delete Employee & User =====================
    // Deletes:
    //  - Employee record
    //  - Related User (optional)
    //  - Image file (if exists)
    //===========================================================
    public function destroy($id)
    {
        // Step 1: Find employee
        $employee = Employee::with("user")->find($id);

        if (!$employee) {
            return response()->json(["message" => "Employee not found"], 404);
        }

        // Step 2: Optionally delete user image from server
        if (
            $employee->user &&
            $employee->user->image &&
            file_exists(public_path($employee->user->image))
        ) {
            unlink(public_path($employee->user->image));
        }

        // Step 3: Delete Employee
        $employee->delete();

        // Step 4: Optionally delete related User
        if ($employee->user) {
            $employee->user->delete();
        }

        return response()->json(
            [
                "status" => "success",
                "message" => "Employee and related user deleted successfully",
            ],
            200
        );
    }


    //===========================================================
    //============== Get Employee Attributes ====================
    // Returns:
    //  - Department list
    //  - Shift list
    //===========================================================
    public function employeeAttributes()
    {
        $departments = Department::all();
        $shifts = WorkingShift::all();

        return response()->json([
            "departments" => $departments,
            "shifts" => $shifts,
        ]);
    }

    //===========================================================
    //============== New Users Management =======================
    // - newusers(): List of users (not active but email verified)
    // - newusersActiveOrDeactive(): Activate user OR delete user
    //===========================================================
    public function newusers()
    {
        $users = User::where("isActive", 0)
            ->where("is_email_varified", true)
            ->get();
        return response()->json($users);
    }

    public function newusersActiveOrDeactive(Request $request)
    {
        $user = User::where("id", $request->id)
            ->where("is_email_varified", true)
            ->latest()
            ->first();

        if ($request->isActive == 1) {
            $user->update(["isActive" => 1]);

            Employee::create([
                "user_id" => $user->id,
                "eid" => "SIT-" . substr(time() . rand(100, 999), -6),
                "fname" => $user->fname,
                "lname" => $user->lname,
                "email" => $user->email,
                "status" => "active",
            ]);

            Mail::send(
                "emails.activation",
                ["userName" => $user->fname],
                function ($message) use ($user) {
                    $message->to($user->email)->subject("Dashboard Activation");
                }
            );
        } else {
            $user->delete();
        }

        return response()->json(
            [
                "message" => "Action Completed",
            ],
            200
        );
    }
}
