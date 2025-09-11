<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\employee;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;
use App\Models\Notification;
use App\Models\EmployeeHasNotification;

class AuthController extends Controller
{

    //========================================================
    //================= Register new user ====================
    //========================================================

    public function register(Request $request)
    {

        if ($request->fname) {
            $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);
        } else {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'image' => 'nullable|image',
            ]);
        }

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $path = public_path('images/' . $filename);
            Image::make($image->getRealPath())->resize(500, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save($path);
        }

        $otp = rand(111111, 999999);

        if ($request->fname) {
            $user = User::updateOrCreate(
                ['email' => $request->email],
                [
                    'fname' => $request->fname,
                    'lname' => $request->lname,
                    'name' => $request->fname . " " . $request->lname,
                    'email_varified_otp' => $otp,
                    'isAggree' => $request->isAggree,
                    'password' => bcrypt($request->password),
                ]
            );


            Mail::send('emails.mailverify', ['userName' => $user->fname, 'otpCode' => $otp], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Email varification OTP');
            });

            return response()->json(['message' => 'Otp Send to Your email ', 'user' => $user]);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'image' => $request->image ? config('app.url') . '/images/' . $filename : null,
                'password' => bcrypt($request->password),
            ]);
            return response()->json(['message' => 'User registered successfully ', 'user' => $user]);
        }
    }


    //========================================================
    //================= Email varification ===================
    //========================================================
    public function varifyemail(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user && $request->otp == $user->email_varified_otp) {
            $user->update([
                'email_verified_at' => now(),
                'is_email_varified' => true
            ]);



            // notifications
            $notification = Notification::create([
                "action" => "New User waiting for approval - " . $user->name
            ]);

            $employees = employee::whereHas('user', function ($q) {
                $q->where('role', 'Admin');
            })->get();

            foreach ($employees as $employee) {
                EmployeeHasNotification::create([
                    "employee_id"    => $employee->id,
                    "notification_id" => $notification->id,
                    "type"        => "register",
                    "is_open"        => false // default unread
                ]);
            }
            // notifications ends

            return response()->json([
                "message" => "Otp verification successful",
            ], 200);
        }

        return response()->json([
            "message" => "Verification failed, wrong otp"
        ], 400);
    }

    //========================================================
    //================= Login as a user ======================
    //========================================================

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $userExists = User::where('email', $request->email)->first();

        if (!$userExists) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $employee = employee::where('user_id', $userExists->id)->latest()->first();

        return response()->json([
            'message' => 'Login successfully',
            'avatar' => $employee->user->image ? url('public/' . $employee->user->image) : "",
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth()->user(),
            'employee' => $employee,
            'status' => 'success',
            'status_code' => 200,
        ]);
    }

    //========================================================
    //================= Forget Password ======================
    //========================================================

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $otp = rand(1000, 999999);

        $user->otp = $otp;
        $user->save();

        // Send email
        Mail::send('emails.otp', ['userName' => $user->name, 'otpCode' => $otp], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Reset OTP');
        });

        return response()->json([
            'message' => 'OTP sent successfully',
            'status' => 'success',
            'status_code' => 200,
        ]);
    }


    //=========================================================
    //========== OTP validation for forget password ===========
    //=========================================================

    public function optValidation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);
        $user = User::where('email', $request->email)->first();

        if ($user->otp != $request->otp) {
            return response()->json(['error' => 'Invalid OTP'], 404);
        }
        $user->otpValidated = true;
        $user->save();
        return response()->json([
            'message' => 'OTP validated successfully',
            'status' => 'success',
            'status_code' => 200,
        ]);
    }

    //==========================================================
    //========== Reset Password after otp validation ===========
    //==========================================================

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('otpValidated', 1)
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Unverified Action'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->otpValidated = false;
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully',
            'status' => 'success',
            'status_code' => 200,
        ]);
    }



    public function profile()
    {
        return response()->json(auth()->user());
    }

    //===========================================================
    //========== Update only user info (Not Employee) ===========
    //===========================================================

    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'image' => 'nullable|image',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images'), $filename);
            $data['image'] = config('app.url') . '/images/' . $filename;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }
    public function deleteProfile(Request $request)
    {
        $user = auth('api')->user();
        $user->delete();
        return response()->json([
            'message' => 'Profile deleted successfully',
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
