<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Target;
use App\Models\Project;
use Carbon\Carbon;

class TargetController extends Controller
{
    //===========================================================
    //============== Get This Month's Target & Achievements ======
    //===========================================================
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get target for this month
        $targetThisMonth = Target::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->first();

        // Get achieved projects (delivered this month)
        $achieved = Project::where('status', 'Delivered')
            ->whereMonth('updated_at', $currentMonth)
            ->whereYear('updated_at', $currentYear)
            ->count();

        return response()->json([
            'status' => 'success',
            'target' => $targetThisMonth ? $targetThisMonth->target_value : 0,
            'achieved' => $achieved,
            'month' => Carbon::now()->format('F Y'),
        ], 200);
    }

    //===========================================================
    //============== Store New Monthly Target ===================
    //===========================================================
    public function store(Request $request)
    {
        $request->validate([
            'target' => 'required|numeric|min:1',
            'month' => 'required|numeric|min:1',
        ]);

        $target = Target::create([
            'target' => $request->target,
            'month' => $request->month,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Target created successfully',
            'data' => $target,
        ], 201);
    }
}
