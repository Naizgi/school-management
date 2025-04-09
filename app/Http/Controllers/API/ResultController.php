<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResultController extends Controller
{


    // ✅ ADD RESULT (Temporarily open to all authenticated users)
    public function addResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'course_id' => 'required|integer',
            'semester' => 'required|string',
            'score' => 'required|numeric',
        ]);

        try {
            // $user = Auth::user(); // Optional: If you still want to log user activity

            Result::create($request->all());

            return response()->json(['message' => 'Result added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add result',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE RESULT (Temporarily open to all authenticated users)
    public function updateResult(Request $request, $result_id)
    {
        $request->validate([
            'score' => 'required|numeric',
        ]);

        try {
            // $user = Auth::user(); // Optional

            $result = Result::findOrFail($result_id);
            $result->update($request->all());

            return response()->json(['message' => 'Result updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update result',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
