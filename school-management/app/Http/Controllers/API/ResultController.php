<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResultController extends Controller
{
    // ✅ Ensure only authenticated users can access this controller
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ ADD RESULT (Only Admins & Instructors)
    public function addResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'course_id' => 'required|integer',
            'semester' => 'required|string',
            'score' => 'required|numeric', // ✅ Changed 'float' to 'numeric'
        ]);

        try {
            $user = Auth::user();

            // ✅ Ensure only Admins or Instructors can add results
            if (!in_array($user->role, ['Admin', 'Instructor'])) {
                return response()->json(['error' => 'Unauthorized action'], 403);
            }

            Result::create($request->all());

            return response()->json(['message' => 'Result added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add result', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ UPDATE RESULT (Only Admins & Instructors)
    public function updateResult(Request $request, $result_id)
    {
        $request->validate([
            'score' => 'required|numeric', // ✅ Changed 'float' to 'numeric'
        ]);

        try {
            $user = Auth::user();

            // ✅ Ensure only Admins or Instructors can update results
            if (!in_array($user->role, ['Admin', 'Instructor'])) {
                return response()->json(['error' => 'Unauthorized action'], 403);
            }

            $result = Result::findOrFail($result_id);
            $result->update($request->all());

            return response()->json(['message' => 'Result updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update result', 'message' => $e->getMessage()], 500);
        }
    }
}
