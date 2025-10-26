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

// ✅ GET LIST OF INSTRUCTORS WITH PAGINATION
public function listInstructors(Request $request)
{
    try {
        $perPage = $request->input('per_page', 20); // Default 20 per page
        $page = $request->input('page', 1);
        $search = $request->input('search'); // Optional search by name, email, phone
        $isActive = $request->input('is_active'); // Optional filter: 1 or 0

        $query = Instructor::query();

        // Optional search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        // Optional active status filter
        if (!is_null($isActive)) {
            $query->where('is_active', $isActive);
        }

        $instructors = $query->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the data
        $transformed = $instructors->map(function ($instructor) {
            return [
                'id' => $instructor->id,
                'user_id' => $instructor->user_id,
                'class_id' => $instructor->class_id,
                'name' => $instructor->name,
                'email' => $instructor->email ?? 'N/A',
                'phone' => $instructor->phone ?? 'N/A',
                'specialization' => $instructor->specialization ?? 'N/A',
                'is_active' => $instructor->is_active,
                'hire_date' => $instructor->hire_date?->format('Y-m-d') ?? 'N/A',
                'created_at' => $instructor->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $instructor->updated_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed,
            'pagination' => [
                'current_page' => $instructors->currentPage(),
                'last_page' => $instructors->lastPage(),
                'per_page' => $instructors->perPage(),
                'total' => $instructors->total(),
                'from' => $instructors->firstItem(),
                'to' => $instructors->lastItem(),
            ],
            'message' => 'Instructors retrieved successfully.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve instructors.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
