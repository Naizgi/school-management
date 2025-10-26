<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\DB;

class CourseAssignmentController extends Controller
{
    // ✅ ASSIGN COURSE TO INSTRUCTOR FOR CLASS
    public function assignCourse(Request $request)
    {
        $validated = $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year' => 'required|string|max:255',
            'semester' => 'nullable|string|max:255',
            'max_students' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Check for duplicate assignment
            $existingAssignment = CourseAssignment::where([
                'instructor_id' => $validated['instructor_id'],
                'course_id' => $validated['course_id'],
                'class_id' => $validated['class_id'],
                'academic_year' => $validated['academic_year']
            ])->first();

            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course is already assigned to the instructor for the specified class and academic year.'
                ], 409);
            }

            $assignment = CourseAssignment::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Course assigned successfully.',
                'data' => $assignment->load(['instructor', 'course', 'class'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to assign course',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ BULK ASSIGN COURSES
    public function bulkAssignCourses(Request $request)
    {
        $validated = $request->validate([
            'assignments' => 'required|array|min:1',
            'assignments.*.instructor_id' => 'required|exists:users,id',
            'assignments.*.course_id' => 'required|exists:courses,id',
            'assignments.*.class_id' => 'required|exists:classes,id',
            'assignments.*.academic_year' => 'required|string|max:255',
            'assignments.*.semester' => 'nullable|string|max:255',
            'assignments.*.max_students' => 'nullable|integer|min:1',
            'assignments.*.notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $createdAssignments = [];
            $skippedAssignments = [];

            foreach ($validated['assignments'] as $assignmentData) {
                // Check for duplicate
                $existing = CourseAssignment::where([
                    'instructor_id' => $assignmentData['instructor_id'],
                    'course_id' => $assignmentData['course_id'],
                    'class_id' => $assignmentData['class_id'],
                    'academic_year' => $assignmentData['academic_year']
                ])->exists();

                if ($existing) {
                    $skippedAssignments[] = $assignmentData;
                    continue;
                }

                $assignment = CourseAssignment::create($assignmentData);
                $createdAssignments[] = $assignment;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk course assignment completed.',
                'summary' => [
                    'created' => count($createdAssignments),
                    'skipped' => count($skippedAssignments)
                ],
                'created_assignments' => $createdAssignments,
                'skipped_assignments' => $skippedAssignments
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to bulk assign courses',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET COURSE ASSIGNMENTS BY CLASS
    public function getAssignmentsByClass($class_id, Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');
            $semester = $request->query('semester');
            $activeOnly = $request->query('active_only', true);

            $query = CourseAssignment::with(['instructor', 'course', 'class'])
                ->where('class_id', $class_id);

            if ($academicYear) {
                $query->where('academic_year', $academicYear);
            }

            if ($semester) {
                $query->where('semester', $semester);
            }

            if ($activeOnly) {
                $query->active();
            }

            $assignments = $query->orderBy('course_id')->get();

            $class = ClassModel::find($class_id);

            return response()->json([
                'success' => true,
                'class_id' => $class_id,
                'class_name' => $class ? "{$class->grade} - {$class->section_name}" : 'N/A',
                'academic_year' => $academicYear,
                'count' => $assignments->count(),
                'data' => $assignments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve course assignments',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET INSTRUCTOR'S COURSE ASSIGNMENTS
    public function getInstructorAssignments($instructor_id, Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');
            $activeOnly = $request->query('active_only', true);

            $query = CourseAssignment::with(['course', 'class'])
                ->where('instructor_id', $instructor_id);

            if ($academicYear) {
                $query->where('academic_year', $academicYear);
            }

            if ($activeOnly) {
                $query->active();
            }

            $assignments = $query->orderBy('academic_year', 'desc')
                ->orderBy('class_id')
                ->get()
                ->groupBy('academic_year');

            return response()->json([
                'success' => true,
                'instructor_id' => $instructor_id,
                'data' => $assignments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve instructor assignments',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET COURSES AVAILABLE FOR CLASS
    public function getAvailableCourses($class_id, Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');

            // Get already assigned courses for this class
            $assignedCourseIds = CourseAssignment::where('class_id', $class_id)
                ->when($academicYear, function ($query) use ($academicYear) {
                    return $query->where('academic_year', $academicYear);
                })
                ->pluck('course_id');

            // Get available courses (not yet assigned)
            $availableCourses = Course::whereNotIn('id', $assignedCourseIds)
                ->where('is_active', true)
                ->get(['id', 'name', 'code', 'credit_hours', 'description']);

            $class = ClassModel::find($class_id);

            return response()->json([
                'success' => true,
                'class_id' => $class_id,
                'class_name' => $class ? "{$class->grade} - {$class->section_name}" : 'N/A',
                'academic_year' => $academicYear,
                'available_courses' => $availableCourses,
                'count' => $availableCourses->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve available courses',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE COURSE ASSIGNMENT
    public function updateAssignment(Request $request, $assignment_id)
    {
        $validated = $request->validate([
            'instructor_id' => 'sometimes|exists:users,id',
            'max_students' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $assignment = CourseAssignment::findOrFail($assignment_id);

            // If changing instructor, check for conflicts
            if (isset($validated['instructor_id']) && $validated['instructor_id'] != $assignment->instructor_id) {
                $conflict = CourseAssignment::where([
                    'instructor_id' => $validated['instructor_id'],
                    'course_id' => $assignment->course_id,
                    'class_id' => $assignment->class_id,
                    'academic_year' => $assignment->academic_year
                ])->where('id', '!=', $assignment_id)->exists();

                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Course is already assigned to this instructor for the same class and academic year.'
                    ], 409);
                }
            }

            $assignment->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Course assignment updated successfully.',
                'data' => $assignment->load(['instructor', 'course', 'class'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update course assignment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE COURSE ASSIGNMENT
    public function deleteAssignment($assignment_id)
    {
        try {
            $assignment = CourseAssignment::findOrFail($assignment_id);

            // Check if assignment is used in timetable
            if ($assignment->timetables()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete course assignment. It is being used in the timetable.'
                ], 409);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Course assignment deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete course assignment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET ALL CLASSES WITH THEIR ASSIGNMENTS
    public function getAllClassesWithAssignments(Request $request)
    {
        try {
            $academicYear = $request->query('academic_year');
            $grade = $request->query('grade');
            $activeOnly = $request->query('active_only', true);

            $query = ClassModel::with(['courseAssignments' => function ($query) use ($academicYear, $activeOnly) {
                if ($academicYear) {
                    $query->where('academic_year', $academicYear);
                }
                if ($activeOnly) {
                    $query->active();
                }
                $query->with(['instructor', 'course']);
            }]);

            if ($grade) {
                $query->where('grade', $grade);
            }

            if ($activeOnly) {
                $query->active();
            }

            $classes = $query->orderBy('grade')
                ->orderBy('section_name')
                ->get()
                ->map(function ($class) {
                    return [
                        'class_id' => $class->id,
                        'class_name' => "{$class->grade} - {$class->section_name}",
                        'grade' => $class->grade,
                        'section_name' => $class->section_name,
                        'homeroom_teacher' => $class->homeroomTeacher->user_name ?? 'N/A',
                        'current_students' => $class->current_students,
                        'max_students' => $class->max_students,
                        'course_assignments' => $class->courseAssignments->map(function ($assignment) {
                            return [
                                'assignment_id' => $assignment->id,
                                'course_name' => $assignment->course->name ?? 'N/A',
                                'instructor_name' => $assignment->instructor->user_name ?? 'N/A',
                                'academic_year' => $assignment->academic_year,
                                'is_active' => $assignment->is_active
                            ];
                        }),
                        'assignments_count' => $class->courseAssignments->count()
                    ];
                });

            return response()->json([
                'success' => true,
                'academic_year' => $academicYear,
                'classes' => $classes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve classes with assignments',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}