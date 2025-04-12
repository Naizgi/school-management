<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    // GET /api/timeslots
    public function index()
    {
        return response()->json(TimeSlot::all(), 200);
    }

    // POST /api/timeslots
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $timeslot = TimeSlot::create($validated);

        return response()->json([
            'message' => 'TimeSlot created successfully.',
            'data' => $timeslot
        ], 201);
    }

    // GET /api/timeslots/{id}
    public function show($id)
    {
        $timeslot = TimeSlot::find($id);

        if (!$timeslot) {
            return response()->json(['message' => 'TimeSlot not found.'], 404);
        }

        return response()->json($timeslot, 200);
    }

    // PUT /api/timeslots/{id}
    public function update(Request $request, $id)
    {
        $timeslot = TimeSlot::find($id);

        if (!$timeslot) {
            return response()->json(['message' => 'TimeSlot not found.'], 404);
        }

        $validated = $request->validate([
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
        ]);

        $timeslot->update($validated);

        return response()->json([
            'message' => 'TimeSlot updated successfully.',
            'data' => $timeslot
        ], 200);
    }

    // DELETE /api/timeslots/{id}
    public function destroy($id)
    {
        $timeslot = TimeSlot::find($id);

        if (!$timeslot) {
            return response()->json(['message' => 'TimeSlot not found.'], 404);
        }

        $timeslot->delete();

        return response()->json(['message' => 'TimeSlot deleted successfully.'], 200);
    }
}
