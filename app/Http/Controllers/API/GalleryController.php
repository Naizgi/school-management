<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gallery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    // ✅ ADD IMAGE
    public function addImage(Request $request)
    {
        // Validate the request
        $request->validate([
            'event_id' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:20048',
            // Optionally validate other fields
            'caption' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'uploaded_by' => 'nullable|integer|exists:users,id', // Assuming uploaded_by is the user ID
        ]);
    
        try {
            // Handle the image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('public/gallery', $imageName);
    
                // Prepare the data for the Gallery record
                $data = [
                    'event_id' => $request->event_id,
                    'image_url' => Storage::url($imagePath), // Full URL to access the image
                    'storage_path' => $imagePath, // Relative path stored in the database
                    'caption' => $request->caption ?? null, // Default to null if no caption
                    'is_featured' => $request->is_featured ?? false, // Default to false if not set
                    'uploaded_by' => $request->uploaded_by ?? null, // Default to null if no user specified
                ];
    
                // Log the data for debugging
                \Log::info('Image data: ', $data);
    
                // Save the image record in the database
                Gallery::create($data);
    
                return response()->json(['message' => 'Image added successfully.']);
            }
    
            return response()->json(['error' => 'No image file found.'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add image', 'message' => $e->getMessage()], 500);
        }
    }
    
    // ✅ VIEW GALLERY BY EVENT ID
    public function viewGallery($event_id)
    {
        try {
            $images = Gallery::where('event_id', $event_id)->get();
            return response()->json($images);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch images', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ FETCH LAST 3 INSERTED GALLERY IMAGES
    public function latestImages()
    {
        try {
            $images = Gallery::latest()->take(3)->get();
            return response()->json([
                'message' => 'Latest 3 images fetched successfully.',
                'data' => $images
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch latest images', 'message' => $e->getMessage()], 500);
        }
    }
}
