<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['viewCourses', 'viewCourse']);
    }

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
        $request->validate([
            'course_name' => 'required|string|unique:courses,course_name|max:255',
        ]);

        if (!Auth::user()->hasRole(['admin', 'instructor'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            Course::create(['course_name' => $request->course_name]);
            return response()->json(['message' => 'Course added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add course', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateCourse(Request $request, $course_id)
    {
        $request->validate([
            'course_name' => 'required|string|max:255|unique:courses,course_name,' . $course_id,
        ]);

        if (!Auth::user()->hasRole(['admin', 'instructor'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $course = Course::findOrFail($course_id);
            $course->update(['course_name' => $request->course_name]);
            return response()->json(['message' => 'Course updated successfully.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Course not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update course', 'message' => $e->getMessage()], 500);
        }
    }
}