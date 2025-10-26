<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gallery;
use Illuminate\Support\Facades\Auth;

class GalleryController extends Controller
{
    // ✅ Apply JWT authentication middleware to ensure the user is authenticated
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ ADD IMAGE (Available to authenticated users only)
    public function addImage(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer',
            'image_url' => 'required|string',
        ]);

        try {
            // Logic to create the gallery image record
            Gallery::create($request->all());
            return response()->json(['message' => 'Image added successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add image', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ VIEW GALLERY (Available to authenticated users only)
    public function viewGallery($event_id)
    {
        try {
            // Fetch images related to the event
            $images = Gallery::where('event_id', $event_id)->get();
            return response()->json($images);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch images', 'message' => $e->getMessage()], 500);
        }
    }
}
