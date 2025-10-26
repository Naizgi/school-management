<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CourseController extends Controller
{
    // View all courses
    public function viewCourses(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search');
            $grade = $request->input('grade');
            $core = $request->input('core');
            $activeOnly = $request->input('active_only', true);
            
            $courses = Course::query()
                ->with(['class:id,grade,section_name', 'instructor:id,user_name,email'])
                ->when($search, function($query) use ($search) {
                    return $query->where(function($q) use ($search) {
                        $q->where('course_name', 'like', "%{$search}%")
                          ->orWhere('course_code', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($grade, function($query) use ($grade) {
                    return $query->whereHas('class', function($q) use ($grade) {
                        $q->where('grade', $grade);
                    });
                })
                ->when(!is_null($core), function($query) use ($core) {
                    return $query->where('core', $core);
                })
                ->when($activeOnly, function($query) {
                    return $query->active();
                })
                ->orderBy('course_name')
                ->paginate($perPage);

            // Transform the response
            $transformedCourses = $courses->getCollection()->map(function ($course) {
                return [
                    'id' => $course->id,
                    'subject' => $course->course_name,
                    'code' => $course->course_code,
                    'credit_hours' => $course->credit_hours,
                    'core' => (bool)$course->core,
                    'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                    'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                    'grade' => $course->class ? $course->class->grade : 'N/A',
                    'section' => $course->class ? $course->class->section_name : 'N/A',
                    'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                    'description' => $course->description,
                    'is_active' => (bool)$course->is_active,
                    'status' => $course->is_active ? 'Active' : 'Inactive',
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at
                ];
            });

            $courses->setCollection($transformedCourses);

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listCourses()
    {
        try {
            // Fetch all courses with their related class (to get grade)
            $courses = Course::with(['class:id,grade,section_name', 'instructor:id,user_name'])
                ->select('id', 'course_name', 'course_code', 'credit_hours', 'core', 'is_active', 'class_id', 'instructor_id', 'description')
                ->active()
                ->get()
                ->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'subject' => $course->course_name,
                        'code' => $course->course_code,
                        'creditHour' => (int)$course->credit_hours,
                        'core' => (bool)$course->core,
                        'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                        'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                        'grade' => $course->class ? $course->class->grade : 'N/A',
                        'section' => $course->class ? $course->class->section_name : 'N/A',
                        'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                        'description' => $course->description,
                        'is_active' => (bool)$course->is_active,
                        'status' => $course->is_active ? 'Active' : 'Inactive'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $courses,
                'message' => 'Courses retrieved successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkManageCourses(Request $request)
    {
        $coursesData = $request->all();

        if (!is_array($coursesData)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request format. Expected an array of courses.'
            ], 400);
        }

        $added = $updated = $deleted = [];
        $errors = [];

        try {
            foreach ($coursesData as $index => $course) {
                if (!isset($course['action'])) {
                    $errors[] = "Row {$index}: Missing action field";
                    continue;
                }

                // Get the class_id from the grade if exists, otherwise null
                $class = isset($course['grade']) ? ClassModel::where('grade', $course['grade'])->first() : null;

                switch (strtolower($course['action'])) {
                    case 'add':
                        if (!isset($course['subject'], $course['code'], $course['creditHour'])) {
                            $errors[] = "Row {$index}: Missing required fields for add action";
                            continue 2;
                        }

                        // Check if course code already exists
                        if (Course::where('course_code', $course['code'])->exists()) {
                            $errors[] = "Row {$index}: Course code '{$course['code']}' already exists";
                            continue 2;
                        }

                        $newCourse = Course::create([
                            'course_name'   => $course['subject'],
                            'course_code'   => $course['code'],
                            'credit_hours'  => $course['creditHour'],
                            'core'          => $course['core'] ?? false,
                            'is_active'     => $course['is_active'] ?? true,
                            'class_id'      => $class?->id,
                            'instructor_id' => $course['instructor_id'] ?? null,
                            'description'   => $course['description'] ?? null,
                            'metadata'      => $course['metadata'] ?? [],
                        ]);

                        $added[] = [
                            'id' => $newCourse->id,
                            'subject' => $newCourse->course_name,
                            'code' => $newCourse->course_code
                        ];
                        break;

                    case 'edit':
                        if (!isset($course['id'])) {
                            $errors[] = "Row {$index}: Missing course ID for edit action";
                            continue 2;
                        }

                        $existing = Course::find($course['id']);
                        if ($existing) {
                            $updateData = [
                                'course_name'   => $course['subject'] ?? $existing->course_name,
                                'course_code'   => $course['code'] ?? $existing->course_code,
                                'credit_hours'  => $course['creditHour'] ?? $existing->credit_hours,
                                'core'          => $course['core'] ?? $existing->core,
                                'is_active'     => $course['is_active'] ?? $existing->is_active,
                                'class_id'      => $class?->id ?? $existing->class_id,
                                'instructor_id' => $course['instructor_id'] ?? $existing->instructor_id,
                                'description'   => $course['description'] ?? $existing->description,
                                'metadata'      => $course['metadata'] ?? $existing->metadata,
                            ];

                            $existing->update($updateData);

                            $updated[] = [
                                'id' => $existing->id,
                                'subject' => $existing->course_name,
                                'code' => $existing->course_code
                            ];
                        } else {
                            $errors[] = "Row {$index}: Course with ID {$course['id']} not found";
                        }
                        break;

                    case 'delete':
                        if (!isset($course['id'])) {
                            $errors[] = "Row {$index}: Missing course ID for delete action";
                            continue 2;
                        }

                        $deletedCourse = Course::find($course['id']);
                        if ($deletedCourse) {
                            $deletedCourse->delete();
                            $deleted[] = [
                                'id' => $deletedCourse->id,
                                'subject' => $deletedCourse->course_name,
                                'code' => $deletedCourse->course_code
                            ];
                        } else {
                            $errors[] = "Row {$index}: Course with ID {$course['id']} not found";
                        }
                        break;

                    default:
                        $errors[] = "Row {$index}: Invalid action '{$course['action']}'";
                        continue 2;
                }
            }

            $response = [
                'success' => true,
                'message' => 'Courses processed successfully',
                'summary' => [
                    'added' => $added,
                    'updated' => $updated,
                    'deleted' => $deleted,
                    'total_processed' => count($added) + count($updated) + count($deleted),
                    'errors_count' => count($errors)
                ]
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process courses.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCoursesByGrade($grade)
    {
        try {
            // Find the class by the grade string with additional details
            $class = ClassModel::where('grade', $grade)
                ->select('id', 'grade', 'section_name', 'homeroom_teacher_id')
                ->with(['homeroomTeacher:id,user_name,email'])
                ->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade not found'
                ], 404);
            }

            // Get courses for this class with instructor and detailed information
            $courses = Course::where('class_id', $class->id)
                ->with(['instructor:id,user_name,email'])
                ->get(['id', 'course_name', 'course_code', 'credit_hours', 'core', 'is_active', 'instructor_id', 'description']);

            // Format the comprehensive response
            $response = [
                'grade_info' => [
                    'grade' => $class->grade,
                    'section' => $class->section_name,
                    'class_id' => $class->id,
                    'homeroom_teacher' => $class->homeroomTeacher ? $class->homeroomTeacher->user_name : 'Not Assigned',
                    'total_courses' => $courses->count(),
                    'core_courses' => $courses->where('core', true)->count(),
                    'elective_courses' => $courses->where('core', false)->count(),
                    'total_credit_hours' => $courses->sum('credit_hours')
                ],
                'courses' => $courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'subject' => $course->course_name,
                        'code' => $course->course_code,
                        'creditHour' => (int)$course->credit_hours,
                        'core' => (bool)$course->core,
                        'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                        'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                        'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                        'instructor_id' => $course->instructor_id,
                        'description' => $course->description,
                        'is_active' => (bool)$course->is_active,
                        'status' => $course->is_active ? 'Active' : 'Inactive'
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $response,
                'message' => 'Courses retrieved successfully for grade ' . $grade
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // View single course
    public function viewCourse($id)
    {
        try {
            $course = Course::with(['class:id,grade,section_name', 'instructor:id,user_name,email'])
                ->findOrFail($id);
            
            // Transform the course data
            $transformedCourse = [
                'id' => $course->id,
                'subject' => $course->course_name,
                'code' => $course->course_code,
                'credit_hours' => $course->credit_hours,
                'core' => (bool)$course->core,
                'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                'grade' => $course->class ? $course->class->grade : 'N/A',
                'section' => $course->class ? $course->class->section_name : 'N/A',
                'class_id' => $course->class_id,
                'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                'instructor_id' => $course->instructor_id,
                'description' => $course->description,
                'is_active' => (bool)$course->is_active,
                'status' => $course->is_active ? 'Active' : 'Inactive',
                'metadata' => $course->metadata,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at
            ];

            return response()->json([
                'success' => true,
                'data' => $transformedCourse,
                'message' => 'Course retrieved successfully.'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addCourse(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'course_name' => 'required|string|max:255',
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'class_id' => 'required|exists:classes,id',
            'instructor_id' => 'required|exists:users,id,role,instructor',
            'description' => 'nullable|string',
            'credit_hours' => 'required|integer|min:1|max:10',
            'core' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);
    
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }
    
        try {
            // Create the course if validation passes
            $course = Course::create([
                'course_name' => $request->course_name,
                'course_code' => $request->course_code,
                'class_id' => $request->class_id,
                'instructor_id' => $request->instructor_id,
                'description' => $request->description,
                'credit_hours' => $request->credit_hours,
                'core' => $request->core ?? false,
                'is_active' => $request->is_active ?? true,
                'metadata' => $request->metadata ?? [],
            ]);

            // Load relationships for response
            $course->load(['class:id,grade,section_name', 'instructor:id,user_name']);

            $transformedCourse = [
                'id' => $course->id,
                'subject' => $course->course_name,
                'code' => $course->course_code,
                'credit_hours' => $course->credit_hours,
                'core' => (bool)$course->core,
                'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                'grade' => $course->class ? $course->class->grade : 'N/A',
                'section' => $course->class ? $course->class->section_name : 'N/A',
                'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                'description' => $course->description,
                'is_active' => (bool)$course->is_active,
                'status' => $course->is_active ? 'Active' : 'Inactive'
            ];
    
            return response()->json([
                'success' => true,
                'message' => 'Course added successfully',
                'data' => $transformedCourse
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateCourse(Request $request, $course_id)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'course_name'   => 'required|string|max:255|unique:courses,course_name,' . $course_id,
            'course_code'   => 'required|string|max:50|unique:courses,course_code,' . $course_id,
            'class_id'      => 'required|exists:classes,id',
            'instructor_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                $user = User::find($value);
                if (!$user || $user->role !== 'instructor') {
                    $fail('The selected instructor is invalid.');
                }
            }],
            'description'   => 'nullable|string',
            'credit_hours'  => 'required|integer|min:1|max:10',
            'core'          => 'boolean',
            'is_active'     => 'boolean',
            'metadata'      => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }
        
        try {
            // Find the course by ID
            $course = Course::findOrFail($course_id);

            // Update the course data
            $course->update([
                'course_name'   => $request->course_name,
                'course_code'   => $request->course_code,
                'class_id'      => $request->class_id,
                'instructor_id' => $request->instructor_id,
                'description'   => $request->description,
                'credit_hours'  => $request->credit_hours,
                'core'          => $request->core ?? $course->core,
                'is_active'     => $request->is_active ?? $course->is_active,
                'metadata'      => $request->metadata ?? $course->metadata,
            ]);

            // Load relationships for response
            $course->load(['class:id,grade,section_name', 'instructor:id,user_name']);

            $transformedCourse = [
                'id' => $course->id,
                'subject' => $course->course_name,
                'code' => $course->course_code,
                'credit_hours' => $course->credit_hours,
                'core' => (bool)$course->core,
                'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                'grade' => $course->class ? $course->class->grade : 'N/A',
                'section' => $course->class ? $course->class->section_name : 'N/A',
                'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                'description' => $course->description,
                'is_active' => (bool)$course->is_active,
                'status' => $course->is_active ? 'Active' : 'Inactive'
            ];

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully.',
                'data' => $transformedCourse
            ], 200);

        } catch (ModelNotFoundException $e) {
            // Handle course not found
            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);
        } catch (\Exception $e) {
            // Return error response if something goes wrong
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Get courses by class ID
    public function getCoursesByClassId($class_id)
    {
        try {
            $courses = Course::where('class_id', $class_id)
                ->with(['class:id,grade,section_name', 'instructor:id,user_name'])
                ->get();

            if ($courses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No courses found for the specified class ID.',
                ], 404);
            }

            $transformedCourses = $courses->map(function ($course) {
                return [
                    'id' => $course->id,
                    'subject' => $course->course_name,
                    'code' => $course->course_code,
                    'credit_hours' => $course->credit_hours,
                    'core' => (bool)$course->core,
                    'core_type' => $course->core ? 'Core Subject' : 'Elective Subject',
                    'course_type' => $this->getCourseType($course->core, $course->credit_hours),
                    'grade' => $course->class ? $course->class->grade : 'N/A',
                    'section' => $course->class ? $course->class->section_name : 'N/A',
                    'instructor' => $course->instructor ? $course->instructor->user_name : 'Not Assigned',
                    'description' => $course->description,
                    'is_active' => (bool)$course->is_active,
                    'status' => $course->is_active ? 'Active' : 'Inactive'
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedCourses,
                'message' => 'Courses retrieved successfully for class ID: ' . $class_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a course by ID
    public function deleteCourse($id)
    {
        try {
            $course = Course::findOrFail($id);
            $courseName = $course->course_name;
            $courseCode = $course->course_code;
            
            $course->delete();

            return response()->json([
                'success' => true,
                'message' => "Course '{$courseName} ({$courseCode})' deleted successfully.",
                'deleted_course' => [
                    'id' => $id,
                    'subject' => $courseName,
                    'code' => $courseCode
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper method to determine course type
    private function getCourseType($isCore, $creditHours)
    {
        if ($isCore) {
            return $creditHours >= 3 ? 'Major Core' : 'General Core';
        } else {
            return $creditHours >= 3 ? 'Major Elective' : 'General Elective';
        }
    }
}