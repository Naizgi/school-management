<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // ✅ MARK ATTENDANCE (Only authenticated users)
    public function markAttendance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'date_of_absence' => 'required|date|before_or_equal:today',
            'reason' => 'required|string|max:255',
            'status' => 'required|string|in:Present,Absent,Late,Half-day',
        ]);

        try {
            $existingAttendance = Attendance::where('student_id', $request->student_id)
                ->where('date_of_absence', $request->date_of_absence)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'error' => 'Attendance already marked for this date.'
                ], 400);
            }

            $attendance = Attendance::create([
                'student_id' => $request->student_id,
                'date_of_absence' => $request->date_of_absence,
                'reason' => $request->reason,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully.',
                'data' => $attendance
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to mark attendance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ VIEW ATTENDANCE FOR SPECIFIC STUDENT WITH PAGINATION AND FILTERS
    public function viewAttendance($student_id, Request $request)
    {
        try {
            // Validate student exists
            $student = Student::with('class')->findOrFail($student_id);

            $perPage = $request->input('per_page', 30);
            $page = $request->input('page', 1);
            $month = $request->input('month');
            $year = $request->input('year', Carbon::now()->year);
            $status = $request->input('status');
            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');

            $query = Attendance::where('student_id', $student_id);

            // Apply filters
            if ($month && $year) {
                $query->whereYear('date_of_absence', $year)
                      ->whereMonth('date_of_absence', $month);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($date_from) {
                $query->whereDate('date_of_absence', '>=', $date_from);
            }

            if ($date_to) {
                $query->whereDate('date_of_absence', '<=', $date_to);
            }

            $attendance = $query->orderBy('date_of_absence', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            if ($attendance->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance records found for this student.'
                ], 404);
            }

            // Calculate statistics
            $statistics = $this->getStudentAttendanceStatistics($student_id, $month, $year);

            // Transform attendance data
            $transformedAttendance = $attendance->map(function ($record) {
                return [
                    'id' => $record->id,
                    'date' => $record->date_of_absence?->format('Y-m-d'),
                    'day' => $record->date_of_absence?->format('l'),
                    'status' => $record->status,
                    'reason' => $record->reason,
                    'created_at' => $record->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $record->updated_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'student_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id ?? $student->id,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A',
                    'class_id' => $student->class_id
                ],
                'statistics' => $statistics,
                'data' => $transformedAttendance,
                'pagination' => [
                    'current_page' => $attendance->currentPage(),
                    'last_page' => $attendance->lastPage(),
                    'per_page' => $attendance->perPage(),
                    'total' => $attendance->total(),
                    'from' => $attendance->firstItem(),
                    'to' => $attendance->lastItem(),
                ],
                'filters' => [
                    'month' => $month,
                    'year' => $year,
                    'status' => $status,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attendance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET STUDENT ATTENDANCE STATISTICS
    public function getStudentAttendanceStats($student_id, Request $request)
    {
        try {
            $student = Student::with('class')->findOrFail($student_id);
            
            $month = $request->input('month');
            $year = $request->input('year', Carbon::now()->year);
            $date_from = $request->input('date_from');
            $date_to = $request->input('date_to');

            $statistics = $this->getStudentAttendanceStatistics($student_id, $month, $year, $date_from, $date_to);

            return response()->json([
                'success' => true,
                'student_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id ?? $student->id,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A'
                ],
                'statistics' => $statistics,
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attendance statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET STUDENT ATTENDANCE SUMMARY BY MONTH
    public function getStudentMonthlySummary($student_id, Request $request)
    {
        try {
            $student = Student::with('class')->findOrFail($student_id);
            $year = $request->input('year', Carbon::now()->year);

            $monthlySummary = [];

            for ($month = 1; $month <= 12; $month++) {
                $startDate = Carbon::create($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                $statistics = $this->getStudentAttendanceStatistics(
                    $student_id, 
                    $month, 
                    $year,
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d')
                );

                $monthlySummary[] = [
                    'month' => $startDate->format('F Y'),
                    'month_number' => $month,
                    'year' => $year,
                    'statistics' => $statistics
                ];
            }

            return response()->json([
                'success' => true,
                'student_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id ?? $student->id,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A'
                ],
                'year' => $year,
                'monthly_summary' => $monthlySummary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve monthly summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET STUDENT ATTENDANCE CALENDAR VIEW
    public function getStudentAttendanceCalendar($student_id, Request $request)
    {
        try {
            $student = Student::with('class')->findOrFail($student_id);
            
            $month = $request->input('month', Carbon::now()->month);
            $year = $request->input('year', Carbon::now()->year);

            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            // Get all attendance records for the month
            $attendanceRecords = Attendance::where('student_id', $student_id)
                ->whereYear('date_of_absence', $year)
                ->whereMonth('date_of_absence', $month)
                ->get()
                ->keyBy(function ($record) {
                    return $record->date_of_absence->format('Y-m-d');
                });

            // Generate calendar days
            $calendar = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateString = $currentDate->format('Y-m-d');
                $attendanceRecord = $attendanceRecords->get($dateString);

                $calendar[] = [
                    'date' => $dateString,
                    'day' => $currentDate->format('l'),
                    'day_number' => $currentDate->day,
                    'is_weekend' => $currentDate->isWeekend(),
                    'attendance' => $attendanceRecord ? [
                        'status' => $attendanceRecord->status,
                        'reason' => $attendanceRecord->reason,
                        'is_present' => in_array($attendanceRecord->status, ['Present', 'Half-day', 'Late'])
                    ] : null
                ];

                $currentDate->addDay();
            }

            // Calculate month statistics
            $monthStatistics = $this->getStudentAttendanceStatistics($student_id, $month, $year);

            return response()->json([
                'success' => true,
                'student_info' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_id' => $student->student_id ?? $student->id,
                    'grade' => $student->class?->grade ?? 'N/A',
                    'section' => $student->class?->section_name ?? 'N/A'
                ],
                'calendar' => [
                    'month' => $startDate->format('F Y'),
                    'month_number' => $month,
                    'year' => $year,
                    'total_days' => count($calendar),
                    'school_days' => collect($calendar)->where('is_weekend', false)->count(),
                    'weekend_days' => collect($calendar)->where('is_weekend', true)->count(),
                    'days' => $calendar
                ],
                'statistics' => $monthStatistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attendance calendar',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ MARK ATTENDANCE IN BULK
    public function markAttendanceBulk(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'attendance_data' => 'required|array',
            'attendance_data.*.student_id' => 'required|exists:students,id',
            'attendance_data.*.status' => 'required|in:Present,Absent,Late,Half-day',
            'attendance_data.*.reason' => 'nullable|string|max:255',
            'date' => 'required|date|before_or_equal:today',
        ]);

        DB::beginTransaction();

        try {
            $date = $request->date;
            $classId = $request->class_id;
            $sectionId = $request->section_id;
            $attendanceData = $request->attendance_data;

            $createdRecords = [];
            $updatedRecords = [];
            $errors = [];

            foreach ($attendanceData as $data) {
                try {
                    // Verify student belongs to the specified class and section
                    $student = Student::where('id', $data['student_id'])
                        ->where('class_id', $classId)
                        ->where('section_id', $sectionId)
                        ->first();

                    if (!$student) {
                        $errors[] = "Student ID {$data['student_id']} does not belong to the specified class/section";
                        continue;
                    }

                    // Check if attendance already exists for this date
                    $existingAttendance = Attendance::where('student_id', $data['student_id'])
                        ->where('date_of_absence', $date)
                        ->first();

                    if ($existingAttendance) {
                        // Update existing record
                        $existingAttendance->update([
                            'status' => $data['status'],
                            'reason' => $data['reason'] ?? $existingAttendance->reason,
                        ]);
                        $updatedRecords[] = $existingAttendance;
                    } else {
                        // Create new record
                        $attendance = Attendance::create([
                            'student_id' => $data['student_id'],
                            'date_of_absence' => $date,
                            'status' => $data['status'],
                            'reason' => $data['reason'] ?? null,
                        ]);
                        $createdRecords[] = $attendance;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to process student ID {$data['student_id']}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk attendance processed successfully.',
                'summary' => [
                    'date' => $date,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'created' => count($createdRecords),
                    'updated' => count($updatedRecords),
                    'errors' => count($errors)
                ],
                'created_records' => $createdRecords,
                'updated_records' => $updatedRecords,
                'errors' => $errors
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to mark bulk attendance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE ATTENDANCE
    public function updateAttendance(Request $request, $attendance_id)
    {
        $request->validate([
            'status' => 'required|string|in:Present,Absent,Late,Half-day',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $attendance = Attendance::findOrFail($attendance_id);

            $attendance->update([
                'status' => $request->status,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully.',
                'data' => $attendance
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update attendance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE ATTENDANCE RECORD
    public function deleteAttendance($attendance_id)
    {
        try {
            $attendance = Attendance::findOrFail($attendance_id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete attendance record',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ HELPER: GET STUDENT ATTENDANCE STATISTICS
    private function getStudentAttendanceStatistics($student_id, $month = null, $year = null, $date_from = null, $date_to = null)
    {
        $query = Attendance::where('student_id', $student_id);

        if ($month && $year) {
            $query->whereYear('date_of_absence', $year)
                  ->whereMonth('date_of_absence', $month);
        }

        if ($date_from) {
            $query->whereDate('date_of_absence', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('date_of_absence', '<=', $date_to);
        }

        $attendanceRecords = $query->get();

        $totalRecords = $attendanceRecords->count();
        $present = $attendanceRecords->where('status', 'Present')->count();
        $absent = $attendanceRecords->where('status', 'Absent')->count();
        $late = $attendanceRecords->where('status', 'Late')->count();
        $halfDay = $attendanceRecords->where('status', 'Half-day')->count();

        // Calculate effective present days (half-day counts as 0.5)
        $effectivePresent = $present + ($halfDay * 0.5) + ($late * 0.75);
        
        // Calculate attendance percentage
        $attendancePercentage = $totalRecords > 0 ? ($effectivePresent / $totalRecords) * 100 : 0;

        // Calculate current month working days (excluding weekends)
        if ($month && $year) {
            $startDate = Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $workingDays = 0;

            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                if (!$date->isWeekend()) {
                    $workingDays++;
                }
            }

            $attendanceRate = $workingDays > 0 ? ($effectivePresent / $workingDays) * 100 : 0;
        } else {
            $workingDays = null;
            $attendanceRate = null;
        }

        return [
            'total_records' => $totalRecords,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'effective_present_days' => round($effectivePresent, 2),
            'attendance_percentage' => round($attendancePercentage, 2),
            'working_days' => $workingDays,
            'attendance_rate' => $attendanceRate ? round($attendanceRate, 2) : null,
            'most_common_status' => $totalRecords > 0 ? $attendanceRecords->groupBy('status')->sortDesc()->keys()->first() : 'No records',
            'last_attendance_date' => $totalRecords > 0 ? $attendanceRecords->sortByDesc('date_of_absence')->first()->date_of_absence->format('Y-m-d') : null
        ];
    }

    // Keep all your existing class-based attendance methods below...
    // VIEW ATTENDANCE BY CLASS ID, VIEW ATTENDANCE BY CLASS AND SECTION, etc.
    // ... [Your existing class-based methods remain unchanged]
    
    // ✅ VIEW ATTENDANCE BY CLASS ID
    public function viewAttendanceByClassId($class_id, Request $request)
    {
        try {
            $date = $request->query('date', Carbon::today()->toDateString());
            $month = $request->query('month');
            $year = $request->query('year', Carbon::now()->year);

            $class = ClassModel::with(['students', 'sections'])->findOrFail($class_id);

            $query = Attendance::with('student')
                ->whereIn('student_id', $class->students->pluck('id'));

            // Filter by specific date or month/year
            if ($date) {
                $query->where('date_of_absence', $date);
            } elseif ($month && $year) {
                $query->whereYear('date_of_absence', $year)
                    ->whereMonth('date_of_absence', $month);
            }

            $attendance = $query->orderBy('date_of_absence', 'desc')
                ->get()
                ->groupBy('date_of_absence');

            if ($attendance->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance records found for this class.'
                ], 404);
            }

            // Calculate summary statistics
            $summary = $this->calculateAttendanceSummary($class->students, $date, $month, $year);

            return response()->json([
                'success' => true,
                'class_id' => $class_id,
                'class_name' => $class->name,
                'date' => $date,
                'summary' => $summary,
                'attendance' => $attendance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attendance for the class',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ HELPER: CALCULATE ATTENDANCE SUMMARY (for class-based methods)
    private function calculateAttendanceSummary($students, $date = null, $month = null, $year = null)
    {
        $studentIds = $students->pluck('id');
        $totalStudents = $students->count();

        $query = Attendance::whereIn('student_id', $studentIds);

        if ($date) {
            $query->where('date_of_absence', $date);
        } elseif ($month && $year) {
            $query->whereYear('date_of_absence', $year)
                ->whereMonth('date_of_absence', $month);
        }

        $attendanceRecords = $query->get();

        $present = $attendanceRecords->where('status', 'Present')->count();
        $absent = $attendanceRecords->where('status', 'Absent')->count();
        $late = $attendanceRecords->where('status', 'Late')->count();
        $halfDay = $attendanceRecords->where('status', 'Half-day')->count();

        $attendanceRate = $totalStudents > 0 ? (($present + $halfDay * 0.5) / $totalStudents) * 100 : 0;

        return [
            'total_students' => $totalStudents,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'attendance_rate' => round($attendanceRate, 2),
            'records_count' => $attendanceRecords->count()
        ];
    }
}