<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instructor;
use Illuminate\Support\Facades\Auth;

class InstructorController extends Controller
{
    /**
     * ✅ Apply JWT authentication via routes
     * (No need for middleware() here to avoid the "undefined method" error)
     */

    // ✅ Reusable response formatters
    private function success($message, $data = [], $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function error($message, $status = 500, $error = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], $status);
    }

    // ✅ Get all classes for the authenticated instructor
    public function getClasses(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'Instructor') {
                return $this->error('Unauthorized access. Only instructors can view classes.', 403);
            }

            $classes = $user->instructor->classes ?? [];

            if (empty($classes)) {
                return $this->error('No classes found for this instructor.', 404);
            }

            return $this->success('Classes retrieved successfully.', $classes);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch classes.', 500, $e->getMessage());
        }
    }

    // ✅ Send communication to parents
    public function communicateWithParents(Request $request)
    {
        try {
            $request->validate([
                'parent_id' => 'required|integer',
                'message' => 'required|string',
            ]);

            // Here you can add logic to send message (e.g., Notification, Email, etc.)

            return $this->success('Message sent successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to send message.', 500, $e->getMessage());
        }
    }

    // ✅ Mark attendance for a student
    public function markAttendance(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'date_of_absence' => 'required|date',
                'reason' => 'required|string',
            ]);

            // Add logic to store attendance here
            // Example:
            // Attendance::create([
            //     'student_id' => $request->student_id,
            //     'date_of_absence' => $request->date_of_absence,
            //     'reason' => $request->reason,
            //     'marked_by' => Auth::id(),
            // ]);

            return $this->success('Attendance marked successfully.');
        } catch (\Exception $e) {
            return $this->error('Failed to mark attendance.', 500, $e->getMessage());
        }
    }

    // ✅ Get list of instructors with pagination and search
    public function listInstructors(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $isActive = $request->input('is_active');

            $query = Instructor::query();

            // 🔍 Apply search filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%");
                });
            }

            // ✅ Filter by active status
            if (!is_null($isActive)) {
                $query->where('is_active', $isActive);
            }

            $instructors = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

            if ($instructors->isEmpty()) {
                return $this->error('No instructors found.', 404);
            }

            // 🧩 Format data
            $formatted = $instructors->getCollection()->map(function ($i) {
                return [
                    'id' => $i->id,
                    'user_id' => $i->user_id,
                    'class_id' => $i->class_id,
                    'name' => $i->name,
                    'email' => $i->email ?? 'N/A',
                    'phone' => $i->phone ?? 'N/A',
                    'specialization' => $i->specialization ?? 'N/A',
                    'is_active' => (bool) $i->is_active,
                    'hire_date' => $i->hire_date ? (string) $i->hire_date : 'N/A',
                    'created_at' => optional($i->created_at)->toDateTimeString(),
                    'updated_at' => optional($i->updated_at)->toDateTimeString(),
                ];
            });

            $instructors->setCollection($formatted);

            return response()->json([
                'success' => true,
                'message' => 'Instructors retrieved successfully.',
                'data' => $instructors->items(),
                'pagination' => [
                    'current_page' => $instructors->currentPage(),
                    'last_page' => $instructors->lastPage(),
                    'per_page' => $instructors->perPage(),
                    'total' => $instructors->total(),
                    'from' => $instructors->firstItem(),
                    'to' => $instructors->lastItem(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve instructors.', 500, $e->getMessage());
        }
    }
}
