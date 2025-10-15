<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request)
    {
        try {
            $query = ClassModel::query();

            // Search filter
            if ($request->has('search')) {
                $query->search($request->search);
            }

            // With teacher filter
            if ($request->boolean('with_teacher')) {
                $query->withTeacher();
            }

            // Pagination
            $perPage = $request->input('per_page', 10);
            $classes = $query->with(['homeroomTeacher:id,user_name,email'])
                            ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'Classes retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve classes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


public function allSections()
{
    try {
        // Fetch all classes
        $sections = \App\Models\ClassModel::select(
            'id',
            'section_name',
            'room_number',
            'max_students',
            'current_students',
            'grade'
        )->get();

        // Format the data
        $formatted = $sections->map(function($section) {
            return [
                'id' => $section->id,
                'sectionName' => $section->section_name,
                'roomNumber' => $section->room_number,
                'capacity' => $section->max_students,
                'currentStudents' => $section->current_students,
                'grade' => $section->grade
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve sections.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function bulkManageSections(Request $request)
{
    $sectionsData = $request->all(); // expects an array of sections with 'action' key

    if (!is_array($sectionsData)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid request format. Expected an array of sections.'
        ], 400);
    }

    try {
        $added = [];
        $updated = [];
        $deleted = [];

        foreach ($sectionsData as $section) {
            if (!isset($section['action'])) {
                continue;
            }

            switch (strtolower($section['action'])) {
                case 'add':
                    if (!isset($section['sectionName'], $section['roomNumber'], $section['grade'])) {
                        continue 2;
                    }

                    $new = \App\Models\ClassModel::create([
                        'section_name'  => $section['sectionName'],
                        'room_number'   => $section['roomNumber'],
                        'grade'         => $section['grade'],
                        'max_students'  => $section['capacity'] ?? 30,
                        'is_active'     => true,
                        'academic_year' => $section['academicYear'] ?? now()->year . '-' . (now()->year + 1),
                    ]);

                    $added[] = $new->id;
                    break;

                case 'edit':
                    if (!isset($section['id'], $section['sectionName'], $section['roomNumber'], $section['grade'])) {
                        continue 2;
                    }

                    $existing = \App\Models\ClassModel::find($section['id']);
                    if ($existing) {
                        $existing->update([
                            'section_name'  => $section['sectionName'],
                            'room_number'   => $section['roomNumber'],
                            'grade'         => $section['grade'],
                            'max_students'  => $section['capacity'] ?? $existing->max_students,
                            'academic_year' => $section['academicYear'] ?? $existing->academic_year,
                        ]);
                        $updated[] = $existing->id;
                    }
                    break;

               case 'delete':
                   if (!isset($section['id'])) {
                      continue 2;
       }

                     $existing = \App\Models\ClassModel::find($section['id']);
                   if ($existing) {
                     $existing->delete(); // soft delete
                     $deleted[] = $existing->id; // add to summary
                    }
                    break;


                default:
                    continue 2;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sections processed successfully.',
            'summary' => [
                'added' => $added,
                'updated' => $updated,
                'deleted' => $deleted
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to process sections.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



public function getSectionsByGrade($grade)
{
    try {
        // Fetch all classes matching the grade
        $sections = \App\Models\ClassModel::where('grade', $grade)
            ->select('id', 'section_name as sectionName')
            ->get();

        if ($sections->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No sections found for the given grade.',
                'grade' => $grade,
                'sections' => []
            ], 404);
        }

        return response()->json([
            'success' => true,
            'grade' => $grade,
            'sections' => $sections
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch sections.',
            'error' => $e->getMessage()
        ], 500);
    }
}







    /**
     * Store a newly created class.
     */
    public function store(Request $request)
    {
        // Enhanced validation
        $validator = Validator::make($request->all(), [
            'class_name' => 'required|string|max:255',
            'academic_year' => 'required|string|max:9',
            'homeroom_teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'instructor')
            ],
            'section' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1|max:100'
        ], [
            'homeroom_teacher_id.exists' => 'The selected teacher must exist and have instructor role'
        ]);
    
        // Add unique check for class_name + academic_year combination
        $validator->after(function ($validator) use ($request) {
            if (ClassModel::where('class_name', $request->class_name)
                ->where('academic_year', $request->academic_year)
                ->exists()) {
                $validator->errors()->add(
                    'class_name', 
                    'This class name already exists for the selected academic year'
                );
            }
        });
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            $class = ClassModel::create([
                'class_name' => $request->class_name,
                'academic_year' => $request->academic_year,
                'homeroom_teacher_id' => $request->homeroom_teacher_id,
                'section' => $request->section,
                'description' => $request->description,
                'max_students' => $request->max_students ?? 30,
                'is_active' => true
            ]);
    
            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'Class created successfully.'
            ], 201);
    
        } catch (\Exception $e) {
            \Log::error('Class creation failed: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    /**
     * Display the specified class.
     */
    public function show($id)
{
    try {
        $class = ClassModel::select([
                'id',
                'class_name',
                'section',
                'academic_year',
                'homeroom_teacher_id'
            ])
            ->with([
                'homeroomTeacher',
                'students',
                'courses'
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $class,
            'message' => 'Class retrieved successfully.'
        ]);

    } catch (\Exception $e) {
        \Log::error("Class fetch error: {$e->getMessage()}");
        return response()->json([
            'success' => false,
            'message' => 'Class not found',
            'error' => env('APP_DEBUG') ? $e->getMessage() : null
        ], 404);
    }
}

    /**
     * Update the specified class.
     */
    public function update(Request $request, $id)
    {
        try {
            $class = ClassModel::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'class_name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('classes')->ignore($class->id)
                ],
                'homeroom_teacher_id' => 'nullable|exists:users,id,role,instructor',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $class->update([
                'class_name' => $request->class_name,
                'homeroom_teacher_id' => $request->homeroom_teacher_id
            ]);

            return response()->json([
                'success' => true,
                'data' => $class,
                'message' => 'Class updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified class.
     */
    public function destroy($id)
    {
        try {
            $class = ClassModel::findOrFail($id);

            // Check if class has students before deleting
            if ($class->students()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete class with assigned students.'
                ], 422);
            }

            $class->delete();

            return response()->json([
                'success' => true,
                'message' => 'Class deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for a class.
     */
    public function statistics($id)
    {
        try {
            $class = ClassModel::withCount(['students', 'courses'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'student_count' => $class->students_count,
                    'course_count' => $class->courses_count,
                    'homeroom_teacher' => $class->homeroomTeacher
                ],
                'message' => 'Class statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get class statistics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all students in a class.
     */
    public function students($id)
    {
        try {
            $class = ClassModel::with('students')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $class->students,
                'message' => 'Class students retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get class students.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}