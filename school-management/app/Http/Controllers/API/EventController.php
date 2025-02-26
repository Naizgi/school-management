<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;

class EventController extends Controller
{
    public function addEvent(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        Event::create($request->all());

        return response()->json(['message' => 'Event added successfully.']);
    }

    public function viewEvents()
    {
        $events = Event::all();
        return response()->json($events);
    }
}
