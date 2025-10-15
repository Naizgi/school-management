<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CourseController extends Controller
{


    // View all courses
    public function viewCourses(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            
            $courses = Course::query()
                ->when($search, function($query) use ($search) {
                    return $query->where('course_name', 'like', "%{$search}%");
                })
                ->orderBy('course_name')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


public function listCourses()
{
    try {
        // Fetch all courses with their related class (to get grade)
        $courses = \App\Models\Course::with('class:id,grade') // Load only the 'grade' column from class table
            ->select('id', 'course_name as subject', 'course_code as code', 'credit_hours as creditHour', 'is_active as core', 'class_id')
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'subject' => $course->subject,
                    'code' => $course->code,
                    'creditHour' => (int)$course->creditHour,
                    'core' => (bool)$course->core,
                    'grade' => $course->class ? $course->class->grade : 'N/A', // Use grade from class table
                ];
            });

        return response()->json($courses, 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve courses.',
            'error' => $e->getMessage(),
        ], 500);
    }
}




public function bulkManageCourses(Request $request)
{
    $coursesData = $request->all();

    if (!is_array($coursesData)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid request format. Expected an array of courses.'
        ], 400);
    }

    $added = $updated = $deleted = [];

    try {
        foreach ($coursesData as $course) {
            if (!isset($course['action'])) {
                continue;
            }

            // Get the class_id from the grade if exists, otherwise null
            $class = isset($course['grade']) ? \App\Models\ClassModel::where('grade', $course['grade'])->first() : null;

            switch (strtolower($course['action'])) {
                case 'add':
                    if (!isset($course['subject'], $course['code'], $course['creditHour'])) {
                        continue 2; // skip if essential fields missing
                    }

                    $newCourse = \App\Models\Course::create([
                        'course_name'   => $course['subject'],
                        'course_code'   => $course['code'],
                        'credit_hours'  => $course['creditHour'],
                        'is_active'     => $course['is_active'] ?? true,
                        'class_id'      => $class?->id,  // can be null
                        'instructor_id' => $course['instructor_id'] ?? null,
                        'description'   => $course['description'] ?? null,
                        'metadata'      => $course['metadata'] ?? [],
                    ]);

                    $added[] = $newCourse->id;
                    break;

                case 'edit':
                    if (!isset($course['id'])) {
                        continue 2;
                    }

                    $existing = \App\Models\Course::find($course['id']);
                    if ($existing) {
                        $existing->update([
                            'course_name'   => $course['subject'] ?? $existing->course_name,
                            'course_code'   => $course['code'] ?? $existing->course_code,
                            'credit_hours'  => $course['creditHour'] ?? $existing->credit_hours,
                            'is_active'     => $course['is_active'] ?? $existing->is_active,
                            'class_id'      => $class?->id ?? $existing->class_id,
                            'instructor_id' => $course['instructor_id'] ?? $existing->instructor_id,
                            'description'   => $course['description'] ?? $existing->description,
                            'metadata'      => $course['metadata'] ?? $existing->metadata,
                        ]);

                        $updated[] = $existing->id;
                    }
                    break;

                case 'delete':
                    if (!isset($course['id'])) {
                        continue 2;
                    }

                    $deletedCourse = \App\Models\Course::find($course['id']);
                    if ($deletedCourse) {
                        $deletedCourse->delete();
                        $deleted[] = $deletedCourse->id;
                    }
                    break;

                default:
                    continue 2;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Courses processed successfully',
            'summary' => [
                'added' => $added,
                'updated' => $updated,
                'deleted' => $deleted,
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to process courses.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function getCoursesByGrade($grade)
{
    try {
        // Find the class by the grade string
        $class = \App\Models\ClassModel::where('grade', $grade)->first();

        if (!$class) {
            return response()->json([
                'success' => false,
                'message' => 'course not found'
            ], 404);
        }

        // Get courses for this class
        $courses = \App\Models\Course::where('class_id', $class->id)
            ->get(['id', 'course_name']);

        // Format the response
        $response = [
            'grade' => $class->grade,
            'courses' => $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'subject' => $course->course_name
                ];
            })
        ];

        return response()->json($response, 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve courses.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    

    // View single course
    public function viewCourse($id)
    {
        try {
            $course = Course::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $course,
                'message' => 'Course retrieved successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addCourse(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'course_name' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'class_id' => 'required|exists:classes,id',  // Ensure class_id exists in classes table
            'instructor_id' => 'required|exists:users,id,role,instructor',  // Ensure instructor exists with role 'instructor'
            'description' => 'nullable|string',
            'credit_hours' => 'required|integer|min:1',
            'is_active' => 'required|boolean',
            'metadata' => 'nullable|array',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
    
        // Create the course if validation passes
        $course = Course::create([
            'course_name' => $request->course_name,
            'course_code' => $request->course_code,
            'class_id' => $request->class_id,
            'instructor_id' => $request->instructor_id,
            'description' => $request->description,
            'credit_hours' => $request->credit_hours,
            'is_active' => $request->is_active,
            'metadata' => $request->metadata,
        ]);
    
        return response()->json(['message' => 'Course added successfully', 'course' => $course], 201);
    }
    
    public function updateCourse(Request $request, $course_id)
    {
        // Validate the request data
        $request->validate([
            'course_name'   => 'required|string|max:255|unique:courses,course_name,' . $course_id,
            'course_code'   => 'required|string|max:50|unique:courses,course_code,' . $course_id,
            'class_id'      => 'required|exists:classes,id',
            'instructor_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (!$user || $user->role !== 'instructor') {
                    $fail('The selected instructor is invalid.');
                }
            }],
            'description'   => 'nullable|string',
            'credit_hours'  => 'required|integer|min:1',
            'is_active'     => 'required|boolean',
            'metadata'      => 'nullable|array',
        ]);
        
        try {
            // Find the course by ID
            $course = Course::findOrFail($course_id);
    
            // Update the course data
            $course->update([
                'course_name'   => $request->course_name,
                'course_code'   => $request->course_code,
                'class_id'      => $request->class_id,
                'instructor_id' => $request->instructor_id,
                'description'   => $request->description,
                'credit_hours'  => $request->credit_hours,
                'is_active'     => $request->is_active,
                'metadata'      => $request->metadata ?? [],
            ]);
    
            // Return success response
            return response()->json(['message' => 'Course updated successfully.'], 200);
        } catch (ModelNotFoundException $e) {
            // Handle course not found
            return response()->json(['error' => 'Course not found'], 404);
        } catch (\Exception $e) {
            // Return error response if something goes wrong
            return response()->json(['error' => 'Failed to update course', 'message' => $e->getMessage()], 500);
        }
    }
    
    
    // Get courses by class ID
public function getCoursesByClassId($class_id)
{
    try {
        $courses = Course::where('class_id', $class_id)->get();

        if ($courses->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No courses found for the specified class ID.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $courses,
            'message' => 'Courses retrieved successfully for class ID: ' . $class_id
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve courses.',
            'error' => $e->getMessage()
        ], 500);
    }
}



// Delete a course by ID
public function deleteCourse($id)
{
    try {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully.'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Course not found.',
            'error' => $e->getMessage()
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete course.',
            'error' => $e->getMessage()
        ], 500);
    }
}


}