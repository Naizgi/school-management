<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notice;

class NoticeController extends Controller
{
    public function addNotice(Request $request)
    {
        $request->validate([
            'notice_type' => 'required|string',
            'student_id' => 'required|integer',
            'title' => 'required|string',
            'description' => 'required|string',
        ]);

        Notice::create($request->all());

        return response()->json(['message' => 'Notice added successfully.']);
    }

    public function viewNotices()
    {
        $notices = Notice::all();
        return response()->json($notices);
    }
}
