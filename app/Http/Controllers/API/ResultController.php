<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;
use App\Models\Student;
use App\Models\Course;
use App\Models\ActivityType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class ResultController extends Controller
{
    // ✅ ADD RESULT (with Activity Type integration)
    public function addResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'course_id' => 'required|integer|exists:courses,id',
            'semester' => 'required|string',
            'activity_type' => 'required|string|exists:activity_types,activity_type',
            'score' => 'required|numeric',
            'max_score' => 'required|numeric|min:0',
            'title' => 'required|string',
            'assessment_date' => 'required|date',
        ]);

        try {
            // Get activity type to use default weight if not provided
            $activityType = ActivityType::where('activity_type', $request->activity_type)->first();
            
            $resultData = $request->all();
            
            // Set default weight from activity type if not provided
            if (!isset($resultData['weight']) && $activityType) {
                $resultData['weight'] = $activityType->default_weight;
            }

            $result = Result::create($resultData);

            return response()->json([
                'message' => 'Result added successfully.',
                'data' => $result->load('activityType')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add result',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE RESULT (with Activity Type integration)
    public function updateResult(Request $request, $result_id)
    {
        $request->validate([
            'score' => 'sometimes|numeric',
            'max_score' => 'sometimes|numeric|min:0',
            'activity_type' => 'sometimes|string|exists:activity_types,activity_type',
            'weight' => 'sometimes|numeric|min:0|max:100',
        ]);

        try {
            $result = Result::findOrFail($result_id);
            
            // If activity_type is being updated, get new default weight
            if ($request->has('activity_type') && $request->activity_type !== $result->activity_type) {
                $activityType = ActivityType::where('activity_type', $request->activity_type)->first();
                if ($activityType && !$request->has('weight')) {
                    $request->merge(['weight' => $activityType->default_weight]);
                }
            }

            $result->update($request->all());

            return response()->json([
                'message' => 'Result updated successfully.',
                'data' => $result->fresh('activityType')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update result',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ FETCH RESULT by student_id and course_id (Updated with Activity Type)
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

            // Find the result using proper column names with activity type relationship
            $result = Result::with([
                    'student:id,name',
                    'course:id,course_name,course_code',
                    'activityType:activity_type,description,default_weight' // Include activity type details
                ])
                ->where('student_id', $request->student_id)
                ->where('course_id', $request->course_id)
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

            // Prepare response data with activity type information
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
                'activity_type_details' => $result->activityType ? [
                    'description' => $result->activityType->description,
                    'default_weight' => $result->activityType->default_weight,
                    'grade_level' => $result->activityType->grade_level,
                    'semester_pattern' => $result->activityType->semester_pattern
                ] : null,
                'title' => $result->title,
                'assessment_date' => $result->formatted_assessment_date,
                'score' => $result->score,
                'max_score' => $result->max_score,
                'weight' => $result->effective_weight, // Use the computed attribute
                'percentage' => $result->calculated_percentage, // Use the computed attribute
                'grade' => $result->grade,
                'is_passing' => $result->isPassing(),
                'remarks' => $result->remarks,
                'is_published' => $result->is_published,
                'published_at' => $result->published_at,
                'comments' => $result->comments
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

    // ✅ NEW: Fetch all results for a student in a course (with activity types)
    public function fetchAllResultsByStudentAndCourse(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|integer|exists:students,id',
                'course_id' => 'required|integer|exists:courses,id',
                'semester' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }

            $query = Result::with([
                    'student:id,name',
                    'course:id,course_name,course_code',
                    'activityType:activity_type,description,default_weight'
                ])
                ->where('student_id', $request->student_id)
                ->where('course_id', $request->course_id);

            if ($request->has('semester')) {
                $query->where('semester', $request->semester);
            }

            $results = $query->orderBy('assessment_date', 'desc')->get();

            if ($results->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No results found for the given student and course',
                    'data' => []
                ], 404);
            }

            // Calculate course statistics
            $totalWeight = 0;
            $weightedScore = 0;
            $hasAllWeights = true;

            foreach ($results as $result) {
                if ($result->effective_weight > 0) {
                    $totalWeight += $result->effective_weight;
                    $weightedScore += ($result->calculated_percentage * $result->effective_weight) / 100;
                } else {
                    $hasAllWeights = false;
                }
            }

            $finalGrade = $hasAllWeights && $totalWeight > 0 ? round($weightedScore / $totalWeight * 100, 2) : null;

            $responseData = [
                'student' => [
                    'id' => $results->first()->student->id,
                    'name' => $results->first()->student->name,
                ],
                'course' => [
                    'id' => $results->first()->course->id,
                    'course_name' => $results->first()->course->course_name,
                    'course_code' => $results->first()->course->code
                ],
                'semester' => $request->semester ?? 'All',
                'results' => $results->map(function($result) {
                    return [
                        'result_id' => $result->result_id,
                        'activity_type' => $result->activity_type,
                        'activity_type_details' => $result->activityType ? [
                            'description' => $result->activityType->description,
                            'default_weight' => $result->activityType->default_weight,
                        ] : null,
                        'title' => $result->title,
                        'assessment_date' => $result->formatted_assessment_date,
                        'score' => $result->score,
                        'max_score' => $result->max_score,
                        'weight' => $result->effective_weight,
                        'percentage' => $result->calculated_percentage,
                        'grade' => $result->grade,
                        'is_passing' => $result->isPassing(),
                        'remarks' => $result->remarks
                    ];
                }),
                'course_statistics' => [
                    'total_activities' => $results->count(),
                    'final_grade' => $finalGrade,
                    'final_letter_grade' => $finalGrade ? $this->calculateLetterGrade($finalGrade) : null,
                    'is_passing' => $finalGrade ? $finalGrade >= 60 : null,
                    'total_weight' => $totalWeight,
                    'has_complete_weights' => $hasAllWeights && $totalWeight == 100
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => 'Results retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Server Error in fetchAllResultsByStudentAndCourse', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // ✅ NEW: Get available activity types for a grade/semester
    public function getActivityTypes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'grade' => 'sometimes|string',
                'semester' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }

            $query = ActivityType::active();

            if ($request->has('grade')) {
                $query->byGrade($request->grade);
            }

            if ($request->has('semester')) {
                $query->bySemester($request->semester);
            }

            $activityTypes = $query->orderBy('activity_type')->get();

            return response()->json([
                'success' => true,
                'data' => $activityTypes,
                'message' => 'Activity types retrieved successfully',
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Server Error in getActivityTypes', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Calculate letter grade from percentage
     */
    private function calculateLetterGrade($percentage): string
    {
        $gradeThresholds = [
            'A' => 90,
            'B' => 80,
            'C' => 70,
            'D' => 60,
            'F' => 0
        ];

        foreach ($gradeThresholds as $grade => $threshold) {
            if ($percentage >= $threshold) {
                return $grade;
            }
        }
        return 'F';
    }
}