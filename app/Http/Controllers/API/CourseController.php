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
    
    
}