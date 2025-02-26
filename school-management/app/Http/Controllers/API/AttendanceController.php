<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'date_of_absence' => 'required|date',
            'reason' => 'required|string',
        ]);

        Attendance::create($request->all());

        return response()->json(['message' => 'Attendance marked successfully.']);
    }

    public function viewAttendance($student_id)
    {
        $attendance = Attendance::where('student_id', $student_id)->get();
        return response()->json($attendance);
    }
}
