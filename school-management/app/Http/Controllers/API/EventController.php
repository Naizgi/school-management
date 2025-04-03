<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    // ✅ Apply JWT authentication middleware to ensure the user is authenticated
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ ADD EVENT (Available to authenticated users only)
    public function addEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        try {
            // Logic to create the event record
            Event::create($request->all());
            return response()->json(['message' => 'Event added successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add event', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ VIEW EVENTS (Available to authenticated users only)
    public function viewEvents()
    {
        try {
            // Fetch all events
            $events = Event::all();
            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch events', 'message' => $e->getMessage()], 500);
        }
    }
}
