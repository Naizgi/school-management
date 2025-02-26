<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gallery;

class GalleryController extends Controller
{
    public function addImage(Request $request)
    {
        $request->validate([
            'event_id' => 'required|integer',
            'image_url' => 'required|string',
        ]);

        Gallery::create($request->all());

        return response()->json(['message' => 'Image added successfully.']);
    }

    public function viewGallery($event_id)
    {
        $images = Gallery::where('event_id', $event_id)->get();
        return response()->json($images);
    }
}
