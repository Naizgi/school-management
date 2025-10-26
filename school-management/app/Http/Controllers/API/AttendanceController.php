<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // ✅ MARK ATTENDANCE (Only authenticated users)
    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date_of_absence' => 'required|date|before_or_equal:today', // Ensure date is not in future
            'reason' => 'required|string|max:255',
        ]);

        try {
    
            if (Gate::denies('mark-attendance')) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

        
            $existingAttendance = Attendance::where('student_id', $request->student_id)
                ->where('date_of_absence', $request->date_of_absence)
                ->first();

            if ($existingAttendance) {
                return response()->json(['error' => 'Attendance already marked for this date.'], 400);
            }

            Attendance::create([
                'student_id' => $request->student_id,
                'date_of_absence' => $request->date_of_absence,
                'reason' => $request->reason,
            ]);

            return response()->json(['message' => 'Attendance marked successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark attendance', 'message' => $e->getMessage()], 500);
        }
    }


    public function viewAttendance($student_id)
    {
        try {
     
            if (Gate::denies('view-attendance', $student_id)) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $attendance = Attendance::where('student_id', $student_id)->get();

            if ($attendance->isEmpty()) {
                return response()->json(['message' => 'No attendance records found.'], 404);
            }

            return response()->json($attendance);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve attendance', 'message' => $e->getMessage()], 500);
        }
    }


    public function markAttendanceBulk(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'date_of_absence' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:255',
        ]);

        try {
            if (Gate::denies('mark-attendance')) {
                return response()->json(['error' => 'Unauthorized access'], 403);
            }

            $studentIds = $request->student_ids;
            $dateOfAbsence = $request->date_of_absence;
            $reason = $request->reason;

            // Filter out students who already have attendance marked for the date
            $existingRecords = Attendance::whereIn('student_id', $studentIds)
                ->where('date_of_absence', $dateOfAbsence)
                ->pluck('student_id')
                ->toArray();

            $newRecords = array_diff($studentIds, $existingRecords);

            if (empty($newRecords)) {
                return response()->json(['error' => 'Attendance already marked for selected students.'], 400);
            }

            // Insert new attendance records in bulk
            $attendanceData = [];
            foreach ($newRecords as $studentId) {
                $attendanceData[] = [
                    'student_id' => $studentId,
                    'date_of_absence' => $dateOfAbsence,
                    'reason' => $reason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Attendance::insert($attendanceData);

            return response()->json([
                'message' => 'Attendance marked successfully for multiple students.',
                'students_marked' => $newRecords,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark attendance', 'message' => $e->getMessage()], 500);
        }
    }
}




