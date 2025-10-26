<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Attendance;
use App\Models\Result;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StudentController extends Controller
{
    // ✅ GET COMPLETE STUDENT DETAILS BY ID
  public function getStudentDetails($id)
{
    try {
        $student = Student::with([
            'class:id,grade,section_name,homeroom_teacher_id,room_number',
            'class.homeroomTeacher:id,user_name,email,phone',
            'attendance' => function($query) {
                $query->select('id', 'student_id', 'date_of_absence', 'status', 'reason', 'subject')
                      ->orderBy('date_of_absence', 'desc')
                      ->limit(30);
            },
        ])->findOrFail($id);

        // 📊 Load supporting stats
        $attendanceStats = $this->getAttendanceStatistics($id);
        $recentActivity = $this->getRecentActivity($id);

        // 🧠 Build response
        $studentDetails = [
            'personal_info' => [
                'id' => $student->id,
                'student_id' => $student->student_id ?? $student->id,
                'name' => $student->name,
                'first_name' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                'last_name' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                'email' => $student->email ?? 'N/A',
                'phone' => $student->phone ?? 'N/A',
                'gender' => $student->gender ?? 'N/A',
                'date_of_birth' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                'age' => $student->date_of_birth ? Carbon::parse($student->date_of_birth)->age : 'N/A',
                'birth_place' => $student->birth_place ?? 'N/A',
                'address' => $student->address ?? 'N/A',
              
            ],
            'parent_info' => [
                'parent_name' => $student->parent_name ?? 'N/A',
                'parent_phone' => $student->parent_phone ?? 'N/A',
                'parent_email' => $student->parent_email ?? 'N/A',
                'parent_occupation' => $student->parent_occupation ?? 'N/A',
                'emergency_contact' => $student->emergency_contact ?? 'N/A',
                'emergency_phone' => $student->emergency_phone ?? 'N/A',
            ],
            'academic_info' => [
                'class' => [
                    'id' => $student->class?->id,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'room_number' => $student->class?->room_number ?? 'N/A',
                    'homeroom_teacher' => $student->class?->homeroomTeacher?->user_name ?? 'Not Assigned',
                    'teacher_email' => $student->class?->homeroomTeacher?->email ?? 'N/A',
                    'teacher_phone' => $student->class?->homeroomTeacher?->phone ?? 'N/A',
                ],
                'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                'admission_number' => $student->admission_number ?? 'N/A',
                'roll_number' => $student->roll_number ?? 'N/A',
                'status' => $student->status ?? 'Active',
                'current_semester' => $student->current_semester ?? '1',
            ],
            'attendance_stats' => $attendanceStats,
            'recent_attendance' => $student->attendance->map(function ($attendance) {
                return [
                    'date' => $attendance->date_of_absence?->format('Y-m-d'),
                    'status' => $attendance->status,
                    'reason' => $attendance->reason,
                    'subject' => $attendance->subject ?? 'General'
                ];
            }),
            'recent_activity' => $recentActivity,
            'metadata' => $student->metadata ?? [],
            'system_info' => [
                'created_at' => $student->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $student->updated_at?->format('Y-m-d H:i:s'),
                'last_login' => $student->last_login?->format('Y-m-d H:i:s') ?? 'Never',
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $studentDetails,
            'message' => 'Student details retrieved successfully.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found.',
            'error' => $e->getMessage()
        ], 404);
    }
}


    // ✅ GET STUDENT PROFILE (Basic profile)
    public function getProfile($student_id)
    {
        try {
            $student = Student::with(['class:id,grade,section_name,homeroom_teacher_id'])
                ->findOrFail($student_id);
                
            return response()->json([
                'success' => true,
                'data' => $student
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Student not found', 
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // ✅ GET LIST OF ALL STUDENTS WITH PAGINATION
    public function listStudents(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $grade = $request->input('grade');
            $section = $request->input('section');
            $status = $request->input('status');
            $gender = $request->input('gender');

            $query = Student::with(['class:id,grade,section_name,homeroom_teacher_id']);

            // Search functionality
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('student_id', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('parent_name', 'like', "%{$search}%")
                      ->orWhereHas('class', function ($classQuery) use ($search) {
                          $classQuery->where('grade', 'like', "%{$search}%")
                                    ->orWhere('section_name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by grade
            if (!empty($grade)) {
                $query->whereHas('class', function ($q) use ($grade) {
                    $q->where('grade', $grade);
                });
            }

            // Filter by section
            if (!empty($section)) {
                $query->whereHas('class', function ($q) use ($section) {
                    $q->where('section_name', $section);
                });
            }

            // Filter by status
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Filter by gender
            if (!empty($gender)) {
                $query->where('gender', $gender);
            }

            // Get students with pagination
            $students = $query->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the data
            $transformedStudents = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id ?? $student->id,
                    'name' => $student->name,
                    'first_name' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                    'last_name' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'class_id' => $student->class_id,
                    'status' => $student->status ?? 'Active',
                    'gender' => $student->gender ?? 'N/A',
                    'email' => $student->email ?? 'N/A',
                    'phone' => $student->phone ?? 'N/A',
                    'date_of_birth' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                    'birth_place' => $student->birth_place ?? 'N/A',
                    'address' => $student->address ?? 'N/A',
                    'parent_name' => $student->parent_name ?? 'N/A',
                    'parent_phone' => $student->parent_phone ?? 'N/A',
                    'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                    'roll_number' => $student->roll_number ?? 'N/A',
                    'created_at' => $student->created_at?->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedStudents,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem(),
                ],
                'filters' => [
                    'search' => $search,
                    'grade' => $grade,
                    'section' => $section,
                    'status' => $status,
                    'gender' => $gender,
                ],
                'message' => 'Students retrieved successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ SEARCH STUDENTS (Comprehensive Search API)
    public function searchStudents(Request $request)
    {
        try {
            $searchTerm = $request->input('q', '');
            $grade = $request->input('grade');
            $section = $request->input('section');
            $status = $request->input('status');
            $gender = $request->input('gender');
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $query = Student::with(['class:id,grade,section_name,homeroom_teacher_id']);

            // Search across multiple fields
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('student_id', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%")
                      ->orWhere('parent_name', 'like', "%{$searchTerm}%")
                      ->orWhere('parent_phone', 'like', "%{$searchTerm}%")
                      ->orWhere('address', 'like', "%{$searchTerm}%")
                      ->orWhere('birth_place', 'like', "%{$searchTerm}%")
                      ->orWhereHas('class', function ($classQuery) use ($searchTerm) {
                          $classQuery->where('grade', 'like', "%{$searchTerm}%")
                                    ->orWhere('section_name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Filter by grade
            if (!empty($grade)) {
                $query->whereHas('class', function ($q) use ($grade) {
                    $q->where('grade', $grade);
                });
            }

            // Filter by section
            if (!empty($section)) {
                $query->whereHas('class', function ($q) use ($section) {
                    $q->where('section_name', $section);
                });
            }

            // Filter by status
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Filter by gender
            if (!empty($gender)) {
                $query->where('gender', $gender);
            }

            // Get total count before pagination
            $totalCount = $query->count();

            // Apply pagination
            $students = $query->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

            // Transform the results
            $transformedStudents = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id ?? $student->id,
                    'name' => $student->name,
                    'firstName' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                    'lastName' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'class_id' => $student->class_id,
                    'status' => $student->status ?? 'Active',
                    'gender' => $student->gender ?? 'N/A',
                    'email' => $student->email ?? 'N/A',
                    'phone' => $student->phone ?? 'N/A',
                    'date_of_birth' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                    'birth_place' => $student->birth_place ?? 'N/A',
                    'address' => $student->address ?? 'N/A',
                    'parent_name' => $student->parent_name ?? 'N/A',
                    'parent_phone' => $student->parent_phone ?? 'N/A',
                    'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                    'created_at' => $student->created_at?->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedStudents,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                    'from' => $students->firstItem(),
                    'to' => $students->lastItem(),
                ],
                'filters' => [
                    'search_term' => $searchTerm,
                    'grade' => $grade,
                    'section' => $section,
                    'status' => $status,
                    'gender' => $gender,
                ],
                'message' => 'Students search completed successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search students.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ ADVANCED SEARCH STUDENTS (More specific searches)
    public function advancedSearchStudents(Request $request)
    {
        try {
            $filters = $request->only([
                'name', 'student_id', 'email', 'phone', 'parent_name', 
                'parent_phone', 'grade', 'section', 'status', 'gender',
                'birth_place', 'address'
            ]);

            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            $query = Student::with(['class:id,grade,section_name']);

            // Apply individual field filters
            if (!empty($filters['name'])) {
                $query->where('name', 'like', "%{$filters['name']}%");
            }

            if (!empty($filters['student_id'])) {
                $query->where('student_id', 'like', "%{$filters['student_id']}%");
            }

            if (!empty($filters['email'])) {
                $query->where('email', 'like', "%{$filters['email']}%");
            }

            if (!empty($filters['phone'])) {
                $query->where('phone', 'like', "%{$filters['phone']}%");
            }

            if (!empty($filters['parent_name'])) {
                $query->where('parent_name', 'like', "%{$filters['parent_name']}%");
            }

            if (!empty($filters['parent_phone'])) {
                $query->where('parent_phone', 'like', "%{$filters['parent_phone']}%");
            }

            if (!empty($filters['birth_place'])) {
                $query->where('birth_place', 'like', "%{$filters['birth_place']}%");
            }

            if (!empty($filters['address'])) {
                $query->where('address', 'like', "%{$filters['address']}%");
            }

            if (!empty($filters['grade'])) {
                $query->whereHas('class', function ($q) use ($filters) {
                    $q->where('grade', $filters['grade']);
                });
            }

            if (!empty($filters['section'])) {
                $query->whereHas('class', function ($q) use ($filters) {
                    $q->where('section_name', $filters['section']);
                });
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            // Date range filters
            if ($request->has('admission_date_from')) {
                $query->whereDate('admission_date', '>=', $request->admission_date_from);
            }

            if ($request->has('admission_date_to')) {
                $query->whereDate('admission_date', '<=', $request->admission_date_to);
            }

            if ($request->has('dob_from')) {
                $query->whereDate('date_of_birth', '>=', $request->dob_from);
            }

            if ($request->has('dob_to')) {
                $query->whereDate('date_of_birth', '<=', $request->dob_to);
            }

            $students = $query->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

            $transformedStudents = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_id' => $student->student_id ?? $student->id,
                    'name' => $student->name,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'status' => $student->status ?? 'Active',
                    'gender' => $student->gender ?? 'N/A',
                    'email' => $student->email ?? 'N/A',
                    'phone' => $student->phone ?? 'N/A',
                    'date_of_birth' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                    'parent_name' => $student->parent_name ?? 'N/A',
                    'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedStudents,
                'pagination' => [
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'per_page' => $students->perPage(),
                    'total' => $students->total(),
                ],
                'filters_applied' => array_filter($filters),
                'message' => 'Advanced search completed successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform advanced search.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ GET STUDENTS BY CLASS AND SECTION
    public function getStudentsByClassAndSection(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                'class_id' => 'required|integer|exists:classes,id',
                'section' => 'required|string|max:255'
            ]);

            $classId = $request->input('class_id');
            $section = $request->input('section');

            // Fetch students by class ID and section
            $students = Student::with('class')
                ->whereHas('class', function ($query) use ($classId, $section) {
                    $query->where('id', $classId)
                          ->where('section_name', $section);
                })
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'firstName' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                        'lastName' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                        'student_id' => $student->student_id ?? $student->id,
                        'grade' => $student->class?->grade ?? 'N/A',
                        'section' => $student->class?->section_name ?? 'N/A',
                        'status' => $student->status ?? 'Active',
                        'gender' => $student->gender ?? 'N/A',
                        'dob' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                        'birthPlace' => $student->birth_place ?? 'N/A',
                        'address' => $student->address ?? 'N/A',
                        'email' => $student->email ?? 'N/A',
                        'phone' => $student->phone ?? 'N/A',
                        'parent_name' => $student->parent_name ?? 'N/A',
                        'parent_phone' => $student->parent_phone ?? 'N/A',
                        'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $students,
                'count' => $students->count(),
                'class_id' => $classId,
                'section' => $section
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students for the specified class and section.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ GET STUDENTS BY GRADE AND SECTION
    public function getStudentsByGradeAndSection(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                'grade' => 'required|string|max:255',
                'section' => 'required|string|max:255'
            ]);

            $grade = $request->input('grade');
            $section = $request->input('section');

            // Fetch students by grade and section
            $students = Student::with('class')
                ->whereHas('class', function ($query) use ($grade, $section) {
                    $query->where('grade', $grade)
                          ->where('section_name', $section);
                })
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'firstName' => $student->name ? explode(' ', $student->name)[0] : 'N/A',
                        'lastName' => $student->name ? substr($student->name, strpos($student->name, ' ') + 1) : 'N/A',
                        'student_id' => $student->student_id ?? $student->id,
                        'grade' => $student->class?->grade ?? 'N/A',
                        'section' => $student->class?->section_name ?? 'N/A',
                        'status' => $student->status ?? 'Active',
                        'gender' => $student->gender ?? 'N/A',
                        'dob' => $student->date_of_birth?->format('Y-m-d') ?? 'N/A',
                        'birthPlace' => $student->birth_place ?? 'N/A',
                        'address' => $student->address ?? 'N/A',
                        'email' => $student->email ?? 'N/A',
                        'phone' => $student->phone ?? 'N/A',
                        'parent_name' => $student->parent_name ?? 'N/A',
                        'parent_phone' => $student->parent_phone ?? 'N/A',
                        'admission_date' => $student->admission_date?->format('Y-m-d') ?? 'N/A',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $students,
                'count' => $students->count(),
                'grade' => $grade,
                'section' => $section
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve students for the specified grade and section.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ GET AVAILABLE CLASSES AND SECTIONS
    public function getAvailableClassesAndSections()
    {
        try {
            $classes = ClassModel::select('id', 'grade', 'section_name')
                ->distinct()
                ->get()
                ->groupBy('grade')
                ->map(function ($sections, $grade) {
                    return [
                        'grade' => $grade,
                        'sections' => $sections->pluck('section_name')->unique()->values(),
                        'class_ids' => $sections->pluck('id')->unique()->values()
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $classes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve available classes and sections.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ GET SEARCH SUGGESTIONS (For autocomplete)
    public function getSearchSuggestions(Request $request)
    {
        try {
            $searchTerm = $request->input('q', '');

            if (empty($searchTerm)) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ], 200);
            }

            $students = Student::where('name', 'like', "%{$searchTerm}%")
                ->orWhere('student_id', 'like', "%{$searchTerm}%")
                ->orWhere('email', 'like', "%{$searchTerm}%")
                ->limit(10)
                ->get(['id', 'name', 'student_id', 'email', 'class_id']);

            $suggestions = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id,
                    'email' => $student->email,
                    'display_text' => "{$student->name} ({$student->student_id})"
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search suggestions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ HELPER METHODS

    /**
     * Calculate attendance statistics for a student
     */
    private function getAttendanceStatistics($studentId)
    {
        $currentMonth = now()->format('Y-m');
        
        $attendance = Attendance::where('student_id', $studentId)
            ->where('date_of_absence', 'like', "{$currentMonth}%")
            ->get();

        $totalDays = now()->daysInMonth;
        $present = $attendance->where('status', 'Present')->count();
        $absent = $attendance->where('status', 'Absent')->count();
        $late = $attendance->where('status', 'Late')->count();
        $halfDay = $attendance->where('status', 'Half-day')->count();

        $attendanceRate = $totalDays > 0 ? (($present + $halfDay * 0.5) / $totalDays) * 100 : 0;

        return [
            'current_month' => $currentMonth,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'attendance_rate' => round($attendanceRate, 2),
            'total_school_days' => $totalDays
        ];
    }

    /**
     * Calculate academic statistics for a student
     */
    private function getAcademicStatistics($studentId)
    {
        $results = Result::where('student_id', $studentId)
            ->with('course')
            ->get();

        $totalCourses = $results->unique('course_id')->count();
        $averageScore = $results->avg('score');
        $highestScore = $results->max('score');
        $lowestScore = $results->min('score');

        $gradeDistribution = $results->groupBy('grade')->map->count();

        return [
            'total_courses' => $totalCourses,
            'average_score' => round($averageScore, 2),
            'highest_score' => $highestScore,
            'lowest_score' => $lowestScore,
            'total_exams' => $results->count(),
            'grade_distribution' => $gradeDistribution
        ];
    }

    /**
     * Get recent activity for a student
     */
    private function getRecentActivity($studentId)
    {
        $recentAttendance = Attendance::where('student_id', $studentId)
            ->orderBy('date_of_absence', 'desc')
            ->limit(5)
            ->get(['date_of_absence', 'status', 'reason']);

        $recentResults = Result::where('student_id', $studentId)
            ->with('course:id,course_name')
            ->orderBy('exam_date', 'desc')
            ->limit(5)
            ->get(['course_id', 'score', 'grade', 'exam_date']);

        return [
            'recent_attendance' => $recentAttendance->map(function ($att) {
                return [
                    'date' => $att->date_of_absence?->format('M d, Y'),
                    'status' => $att->status,
                    'reason' => $att->reason
                ];
            }),
            'recent_results' => $recentResults->map(function ($result) {
                return [
                    'course' => $result->course->course_name,
                    'score' => $result->score,
                    'grade' => $result->grade,
                    'date' => $result->exam_date?->format('M d, Y')
                ];
            })
        ];
    }
}