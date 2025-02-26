<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeTable;

class TimeTableController extends Controller
{
    public function viewTimetable($class_id)
    {
        $timetable = TimeTable::where('class_id', $class_id)->get();
        return response()->json($timetable);
    }

    public function updateTimetable(Request $request, $timetable_id)
    {
        $request->validate([
            'day_of_week' => 'required|string',
            'timeslot_id' => 'required|integer',
            'course_id' => 'required|integer',
        ]);

        $timetable = TimeTable::findOrFail($timetable_id);
        $timetable->update($request->all());

        return response()->json(['message' => 'Timetable updated successfully.']);
    }
}
