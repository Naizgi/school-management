<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimeTable;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class TimetableController extends Controller
{
    // ✅ Ensure only authenticated users access this controller
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ VIEW TIMETABLE (Protected)
    public function viewTimetable($class_id)
    {
        try {
            $timetable = TimeTable::where('class_id', $class_id)->get();

            if ($timetable->isEmpty()) {
                return response()->json(['message' => 'No timetable found for this class'], 404);
            }

            return response()->json($timetable, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch timetable', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ UPDATE TIMETABLE (Protected)
    public function updateTimetable(Request $request, $timetable_id)
    {
        try {
            $request->validate([
                'day_of_week' => 'required|string',
                'timeslot_id' => 'required|integer',
                'course_id' => 'required|integer',
            ]);

            $timetable = TimeTable::findOrFail($timetable_id);
            $timetable->update($request->all());

            return response()->json(['message' => 'Timetable updated successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update timetable', 'message' => $e->getMessage()], 500);
        }
    }
}
