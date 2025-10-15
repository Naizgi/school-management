<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentController extends Controller
{
    // ✅ Ensure only authenticated users can access this controller

    // ✅ GET STUDENT PROFILE (Protected)
    public function getProfile($student_id)
    {
        try {
            $student = Student::findOrFail($student_id);
            return response()->json($student, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Student not found', 'message' => $e->getMessage()], 404);
        }
    }

    // ✅ GET LIST OF ALL STUDENTS
    public function listStudents()
{
    try {
        // Fetch all students with their related class
        $students = Student::with('class') // eager load class
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'firstName' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                    'lastName' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'status' => $student->status ?? 'Active',
                    'gender' => $student->gender ?? 'N/A',
                    'dob' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                    'birthPlace' => $student->birth_place ?? 'N/A',
                    'address' => $student->address ?? 'N/A',
                ];
            });

        return response()->json($students, 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve students.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
