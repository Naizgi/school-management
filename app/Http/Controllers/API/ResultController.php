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


    // ✅ FETCH RESULT by student_id and course_id
    public function fetchResultByStudentAndCourse(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
        ]);
    
        try {
            // Eager load student and course relationships
            $result = Result::where('student_id', $request->student_id)
                            ->where('course_id', $request->course_id)
                            ->with(['student', 'course'])  // Eager load relationships
                            ->first();
    
            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Result not found.'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data' => $result,  // This will automatically include appended attributes
                'message' => 'Result retrieved successfully.'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch result.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    

}
