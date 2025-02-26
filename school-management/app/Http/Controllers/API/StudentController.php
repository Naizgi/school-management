<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;

class StudentController extends Controller
{
    public function getProfile($student_id)
    {
        return response()->json(Student::findOrFail($student_id));
    }
}
