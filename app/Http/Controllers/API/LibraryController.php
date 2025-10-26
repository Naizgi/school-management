<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Library;  // ✅ Correct model
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    // ✅ ADD A BOOK (Accessible only by Admin or authorized users)
    public function addBook(Request $request)
    {
        $user = Auth::user();
    
        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'category' => 'required|string',
            'description' => 'required|string',
            'cover_image' => 'required|string',
            'file_url' => 'required|string',
        ]);
    
        try {
            Library::create($request->all());  // ✅ Changed Book to Library
            return response()->json(['message' => 'Book added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add book', 'message' => $e->getMessage()], 500);
        }
    }
    
    // ✅ VIEW ALL BOOKS (Available to authenticated users)
    public function viewBooks()
    {
        try {
            $books = Library::all();  // ✅ Changed Book to Library
            return response()->json($books, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch books', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ SEARCH BOOKS (Available to authenticated users)
    public function searchBooks(Request $request)
    {
        $query = $request->query('query');
        try {
            $books = Library::where('title', 'like', "%{$query}%")
                ->orWhere('author', 'like', "%{$query}%")
                ->get();  // ✅ Changed Book to Library
            return response()->json($books, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search books', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ DOWNLOAD A BOOK (Available to authenticated users)
    public function downloadBook($book_id)
    {
        try {
            $book = Library::findOrFail($book_id);  // ✅ Changed Book to Library
            return response()->download(storage_path("app/{$book->file_url}"));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download book', 'message' => $e->getMessage()], 500);
        }
    }
}
