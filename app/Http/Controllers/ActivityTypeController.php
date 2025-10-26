<?php
// app/Http/Controllers/ActivityTypeController.php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityType::active()->with('results');
        
        if ($request->has('grade')) {
            $query->byGrade($request->grade);
        }
        
        if ($request->has('semester')) {
            $query->bySemester($request->semester);
        }
        
        $activityTypes = $query->orderBy('activity_type')->get();
        
        return response()->json([
            'success' => true,
            'data' => $activityTypes
        ]);
    }

    public function show($id): JsonResponse
    {
        $activityType = ActivityType::active()->find($id);
        
        if (!$activityType) {
            return response()->json([
                'success' => false,
                'message' => 'Activity type not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $activityType->load('results')
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => 'required|string|max:100|unique:activity_types',
            'description' => 'nullable|string',
            'default_weight' => 'required|numeric|min:0|max:100',
            'grade_level' => 'nullable|string|max:50',
            'semester_pattern' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $activityType = ActivityType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity type created successfully',
            'data' => $activityType
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $activityType = ActivityType::find($id);
        
        if (!$activityType) {
            return response()->json([
                'success' => false,
                'message' => 'Activity type not found'
            ], 404);
        }

        $validated = $request->validate([
            'activity_type' => 'sometimes|string|max:100|unique:activity_types,activity_type,' . $id . ',activity_type_id',
            'description' => 'nullable|string',
            'default_weight' => 'sometimes|numeric|min:0|max:100',
            'grade_level' => 'nullable|string|max:50',
            'semester_pattern' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $activityType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Activity type updated successfully',
            'data' => $activityType
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $activityType = ActivityType::find($id);
        
        if (!$activityType) {
            return response()->json([
                'success' => false,
                'message' => 'Activity type not found'
            ], 404);
        }

        // Soft delete or check if used in results
        if ($activityType->results()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete activity type as it is being used in results'
            ], 422);
        }

        $activityType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Activity type deleted successfully'
        ]);
    }
}