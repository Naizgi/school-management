<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notice;
use Illuminate\Support\Facades\Auth;

class NoticeController extends Controller
{
    // ✅ Apply JWT authentication middleware
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    // ✅ ADD A NOTICE
    public function addNotice(Request $request)
    {
        $request->validate([
            'notice_type' => 'required|string',
            'student_id' => 'required|integer',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        try {
            $user = Auth::user();

            // ✅ Ensure only Admin or authorized role can add notices (if needed)
            if ($user->role !== 'Admin') {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // ✅ Create the notice
            Notice::create($request->all());

            return response()->json(['message' => 'Notice added successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to add notice', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ VIEW ALL NOTICES
    public function viewNotices()
    {
        try {
            $notices = Notice::all();
            return response()->json($notices, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch notices', 'message' => $e->getMessage()], 500);
        }
    }
}
