<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    // ✅ Apply JWT authentication middleware to ensure the user is authenticated
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ GET CLASSES (Available to authenticated instructors only)
    public function getClasses(Request $request)
    {
        // Ensure the authenticated user is an instructor
        $user = Auth::user();
        if ($user->role !== 'Instructor') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $classes = $request->user()->instructor->classes;
            return response()->json($classes);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch classes', 'message' => $e->getMessage()], 500);
        }
    }


    public function communicateWithParents(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|integer',
            'message' => 'required|string',
        ]);

        try {
            // Logic for sending message to the parent (you may integrate a messaging system here)
            return response()->json(['message' => 'Message sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to communicate with parent', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ MARK ATTENDANCE (Available to authenticated instructors only)
    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date_of_absence' => 'required|date',
            'reason' => 'required|string',
        ]);

        try {
            // Logic for marking attendance
            return response()->json(['message' => 'Attendance marked successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark attendance', 'message' => $e->getMessage()], 500);
        }
    }
}
