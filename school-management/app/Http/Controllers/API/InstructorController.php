<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Student;

class InstructorController extends Controller
{
    public function getClasses(Request $request)
    {
        return response()->json($request->user()->instructor->classes);
    }

    public function communicateWithParents(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        return response()->json(['message' => 'Message sent successfully.']);
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date_of_absence' => 'required|date',
            'reason' => 'required|string',
        ]);

        return response()->json(['message' => 'Attendance marked successfully.']);
    }
}
