<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimeTable;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{
    // ✅ Ensure only authenticated users access this controller

    // ✅ CREATE TIMETABLE ENTRY
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'timeslot_id' => 'required|exists:timeslots,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        // Check for conflicts
        $conflict = TimeTable::where('class_id', $validated['class_id'])
            ->where('timeslot_id', $validated['timeslot_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('is_active', true)
            ->first();

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => 'Timetable conflict: This class already has a course scheduled at this timeslot and day.',
                'conflicting_entry' => $conflict
            ], 409);
        }

        $timetable = TimeTable::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Timetable created successfully',
            'data' => $timetable
        ], 201);
    }

    // ✅ BULK CREATE WEEKLY TIMETABLE
    public function createWeeklyTimetable(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'timetable_entries' => 'required|array|min:1',
            'timetable_entries.*.course_id' => 'required|exists:courses,id',
            'timetable_entries.*.timeslot_id' => 'required|exists:timeslots,id',
            'timetable_entries.*.day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean|true',
        ]);

        DB::beginTransaction();

        try {
            $createdEntries = [];
            $conflicts = [];

            foreach ($validated['timetable_entries'] as $entry) {
                // Check for conflicts
                $conflict = TimeTable::where('class_id', $validated['class_id'])
                    ->where('timeslot_id', $entry['timeslot_id'])
                    ->where('day_of_week', $entry['day_of_week'])
                    ->where('is_active', true)
                    ->first();

                if ($conflict) {
                    $conflicts[] = [
                        'requested_entry' => $entry,
                        'conflicting_with' => $conflict
                    ];
                    continue;
                }

                // Create timetable entry
                $timetableEntry = TimeTable::create([
                    'class_id' => $validated['class_id'],
                    'course_id' => $entry['course_id'],
                    'timeslot_id' => $entry['timeslot_id'],
                    'day_of_week' => $entry['day_of_week'],
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                $createdEntries[] = $timetableEntry;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Weekly timetable created successfully',
                'created_entries' => $createdEntries,
                'conflicts' => $conflicts,
                'summary' => [
                    'created' => count($createdEntries),
                    'conflicts' => count($conflicts)
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create weekly timetable',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ VIEW TIMETABLE BY CLASS ID
    public function viewTimetable($class_id)
    {
        try {
            $timetable = TimeTable::with(['course', 'timeslot', 'class'])
                ->where('class_id', $class_id)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('timeslot_id')
                ->get()
                ->groupBy('day_of_week');

            if ($timetable->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No timetable found for this class'
                ], 404);
            }

            // Format the response by day
            $formattedTimetable = [];
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach ($daysOfWeek as $day) {
                if (isset($timetable[$day])) {
                    $formattedTimetable[$day] = $timetable[$day]->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'timeslot' => $entry->timeslot->name ?? 'N/A',
                            'start_time' => $entry->timeslot->start_time ?? 'N/A',
                            'end_time' => $entry->timeslot->end_time ?? 'N/A',
                            'course' => $entry->course->name ?? 'N/A',
                            'course_code' => $entry->course->code ?? 'N/A',
                            'teacher' => $entry->course->teacher->name ?? 'N/A',
                            'is_active' => $entry->is_active,
                        ];
                    });
                } else {
                    $formattedTimetable[$day] = [];
                }
            }

            return response()->json([
                'success' => true,
                'class_id' => $class_id,
                'class_name' => $timetable->first()->first()->class->name ?? 'N/A',
                'timetable' => $formattedTimetable
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch timetable',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ VIEW TIMETABLE BY CLASS ID AND DAY
    public function viewTimetableByDay($class_id, $day)
    {
        try {
            $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            if (!in_array($day, $validDays)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid day. Must be one of: ' . implode(', ', $validDays)
                ], 400);
            }

            $timetable = TimeTable::with(['course', 'timeslot', 'class'])
                ->where('class_id', $class_id)
                ->where('day_of_week', $day)
                ->where('is_active', true)
                ->orderBy('timeslot_id')
                ->get()
                ->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'timeslot' => $entry->timeslot->name ?? 'N/A',
                        'start_time' => $entry->timeslot->start_time ?? 'N/A',
                        'end_time' => $entry->timeslot->end_time ?? 'N/A',
                        'course' => $entry->course->name ?? 'N/A',
                        'course_code' => $entry->course->code ?? 'N/A',
                        'teacher' => $entry->course->teacher->name ?? 'N/A',
                        'is_active' => $entry->is_active,
                    ];
                });

            return response()->json([
                'success' => true,
                'class_id' => $class_id,
                'day' => $day,
                'timetable' => $timetable
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch timetable for the day',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ UPDATE TIMETABLE ENTRY
    public function updateTimetable(Request $request, $timetable_id)
    {
        try {
            $request->validate([
                'course_id' => 'required|exists:courses,id',
                'timeslot_id' => 'required|exists:timeslots,id',
                'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'is_active' => 'boolean',
            ]);

            $timetable = TimeTable::findOrFail($timetable_id);

            // Check for conflicts (excluding current entry)
            $conflict = TimeTable::where('class_id', $timetable->class_id)
                ->where('timeslot_id', $request->timeslot_id)
                ->where('day_of_week', $request->day_of_week)
                ->where('is_active', true)
                ->where('id', '!=', $timetable_id)
                ->first();

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Timetable conflict: This class already has a course scheduled at this timeslot and day.',
                    'conflicting_entry' => $conflict
                ], 409);
            }

            $timetable->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Timetable updated successfully.',
                'data' => $timetable
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update timetable',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ DELETE TIMETABLE ENTRY
    public function destroy($timetable_id)
    {
        try {
            $timetable = TimeTable::findOrFail($timetable_id);
            $timetable->delete();

            return response()->json([
                'success' => true,
                'message' => 'Timetable entry deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete timetable entry',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET AVAILABLE TIMESLOTS
    public function getAvailableTimeslots()
    {
        try {
            $timeslots = Timeslot::where('is_active', true)
                ->orderBy('start_time')
                ->get(['id', 'name', 'start_time', 'end_time', 'duration']);

            return response()->json([
                'success' => true,
                'data' => $timeslots
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch timeslots',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ CHECK TIMETABLE CONFLICTS
    public function checkConflicts(Request $request)
    {
        try {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
                'timeslot_id' => 'required|exists:timeslots,id',
                'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'exclude_id' => 'nullable|exists:timetables,id' // For update operations
            ]);

            $query = TimeTable::with(['course', 'class'])
                ->where('class_id', $request->class_id)
                ->where('timeslot_id', $request->timeslot_id)
                ->where('day_of_week', $request->day_of_week)
                ->where('is_active', true);

            if ($request->has('exclude_id')) {
                $query->where('id', '!=', $request->exclude_id);
            }

            $conflicts = $query->get();

            return response()->json([
                'success' => true,
                'has_conflicts' => $conflicts->isNotEmpty(),
                'conflicts' => $conflicts,
                'conflict_count' => $conflicts->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to check conflicts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ GET TIMETABLE FOR TEACHER
    public function getTeacherTimetable($teacher_id)
    {
        try {
            $timetable = TimeTable::with(['course', 'timeslot', 'class'])
                ->whereHas('course', function ($query) use ($teacher_id) {
                    $query->where('teacher_id', $teacher_id);
                })
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('timeslot_id')
                ->get()
                ->groupBy('day_of_week');

            $formattedTimetable = [];
            $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            foreach ($daysOfWeek as $day) {
                if (isset($timetable[$day])) {
                    $formattedTimetable[$day] = $timetable[$day]->map(function ($entry) {
                        return [
                            'timeslot' => $entry->timeslot->name ?? 'N/A',
                            'start_time' => $entry->timeslot->start_time ?? 'N/A',
                            'end_time' => $entry->timeslot->end_time ?? 'N/A',
                            'course' => $entry->course->name ?? 'N/A',
                            'class' => $entry->class->name ?? 'N/A',
                            'class_id' => $entry->class_id,
                        ];
                    });
                } else {
                    $formattedTimetable[$day] = [];
                }
            }

            return response()->json([
                'success' => true,
                'teacher_id' => $teacher_id,
                'timetable' => $formattedTimetable
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch teacher timetable',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}