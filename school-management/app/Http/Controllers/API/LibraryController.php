<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;

class LibraryController extends Controller
{
    public function addBook(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'category' => 'required|string',
            'description' => 'required|string',
            'cover_image' => 'required|string',
            'file_url' => 'required|string',
        ]);

        Book::create($request->all());

        return response()->json(['message' => 'Book added successfully.']);
    }

    public function viewBooks()
    {
        $books = Book::all();
        return response()->json($books);
    }

    public function searchBooks(Request $request)
    {
        $query = $request->query('query');
        $books = Book::where('title', 'like', "%{$query}%")
            ->orWhere('author', 'like', "%{$query}%")
            ->get();
        return response()->json($books);
    }

    public function downloadBook($book_id)
    {
        $book = Book::findOrFail($book_id);
        return response()->download(storage_path("app/{$book->file_url}"));
    }
}
