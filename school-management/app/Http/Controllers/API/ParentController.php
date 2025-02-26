<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class ParentController extends Controller
{
    public function getStudents(Request $request)
    {
        return response()->json($request->user()->parent->students);
    }

    public function updateStudentProfile(Request $request, $student_id)
    {
        $request->validate([
            'name' => 'required|string',
            'class_id' => 'required|integer',
            'profile_picture' => 'nullable|string',
        ]);

        $student = Student::findOrFail($student_id);
        $student->update($request->all());

        return response()->json(['message' => 'Profile updated successfully.']);
    }
}
