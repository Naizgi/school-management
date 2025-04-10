<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Support\Facades\Validator;
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
  /**
 * Fetch result by student ID and course ID
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 */
public function fetchResultByStudentAndCourse(Request $request)
{
    \DB::enableQueryLog();
    \Log::info('API Request:', ['method' => __METHOD__, 'input' => $request->all()]);

    try {
        // Custom validation with proper column names
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer',
            'course_id' => 'required|integer'
        ]);

        // Manual existence checks
        $validator->after(function ($validator) use ($request) {
            if (!Student::where('id', $request->student_id)->exists()) {
                $validator->errors()->add('student_id', 'The selected student does not exist.');
            }
            
            if (!Course::where('id', $request->course_id)->exists()) {
                $validator->errors()->add('course_id', 'The selected course does not exist.');
            }
        });

        if ($validator->fails()) {
            \Log::error('Validation Failed', ['errors' => $validator->errors()->all()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
                '_status' => 422,
            ], 422);
        }

        // Find the result using proper column names
        $result = Result::with([
                'student:id,name',  // Using id instead of student_id
                'course:id,course_name,course_code'  // Using id instead of course_id
            ])
            ->where('student_id', $request->student_id)  // This should match your results table column
            ->where('course_id', $request->course_id)    // This should match your results table column
            ->first();

        if (!$result) {
            \Log::warning('Result Not Found', [
                'student_id' => $request->student_id,
                'course_id' => $request->course_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Result not found for the given student and course',
                '_status' => 404,
            ], 404);
        }

        // Prepare response data
        $responseData = [
            'result_id' => $result->result_id,
            'student' => [
                'id' => $result->student->id,
                'name' => $result->student->name,
            
            ],
            'course' => [
                'id' => $result->course->id,
                'course_name' => $result->course->course_name,
                'course_code' => $result->course->code
            ],
            'semester' => $result->semester,
            'activity_type' => $result->activity_type,
            'title' => $result->title,
            'assessment_date' => $result->formatted_assessment_date,
            'score' => $result->score,
            'max_score' => $result->max_score,
            'percentage' => $result->percentage,
            'grade' => $result->grade,
            'is_passing' => $result->isPassing(),
            'remarks' => $result->remarks
        ];

        \Log::info('Result Found', ['result_id' => $result->result_id]);
        
        return response()->json([
            'success' => true,
            'data' => $responseData,
            'message' => 'Result retrieved successfully',
            '_status' => 200,
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Server Error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal server error occurred',
            'error' => config('app.debug') ? $e->getMessage() : null,
            '_status' => 500,
        ], 500);
    }
}
    
    
    

}
