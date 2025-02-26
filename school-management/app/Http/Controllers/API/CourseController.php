<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    public function addCourse(Request $request)
    {
        $request->validate(['course_name' => 'required|string']);
        Course::create($request->all());
        return response()->json(['message' => 'Course added successfully.']);
    }

    public function updateCourse(Request $request, $course_id)
    {
        $request->validate(['course_name' => 'required|string']);
        $course = Course::findOrFail($course_id);
        $course->update($request->all());
        return response()->json(['message' => 'Course updated successfully.']);
    }
}
