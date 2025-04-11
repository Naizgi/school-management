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
}
