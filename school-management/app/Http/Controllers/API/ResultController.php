<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;

class ResultController extends Controller
{
    public function addResult(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'course_id' => 'required|integer',
            'semester' => 'required|string',
            'score' => 'required|float',
        ]);

        Result::create($request->all());

        return response()->json(['message' => 'Result added successfully.']);
    }

    public function updateResult(Request $request, $result_id)
    {
        $request->validate(['score' => 'required|float']);
        $result = Result::findOrFail($result_id);
        $result->update($request->all());
        return response()->json(['message' => 'Result updated successfully.']);
    }
}
