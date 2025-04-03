<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class LibraryController extends Controller
{
    // ✅ Apply JWT authentication middleware
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ ADD A BOOK (Accessible only by Admin or authorized users)
    public function addBook(Request $request)
    {
        // Ensure that only admins or specific users can add books
        $user = Auth::user();
        if ($user->role !== 'Admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'category' => 'required|string',
            'description' => 'required|string',
            'cover_image' => 'required|string',
            'file_url' => 'required|string',
        ]);

        try {
            Book::create($request->all());
            return response()->json(['message' => 'Book added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add book', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ VIEW ALL BOOKS (Available to authenticated users)
    public function viewBooks()
    {
        try {
            $books = Book::all();
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
            $books = Book::where('title', 'like', "%{$query}%")
                ->orWhere('author', 'like', "%{$query}%")
                ->get();
            return response()->json($books, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search books', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ DOWNLOAD A BOOK (Available to authenticated users)
    public function downloadBook($book_id)
    {
        try {
            $book = Book::findOrFail($book_id);
            return response()->download(storage_path("app/{$book->file_url}"));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download book', 'message' => $e->getMessage()], 500);
        }
    }
}
