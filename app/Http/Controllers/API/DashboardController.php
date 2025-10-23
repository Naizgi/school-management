<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Notice;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // GET /api/dashboard/summary
    public function summary()
    {
        try {
            $studentsCount = Student::count();
            $maleStudents = Student::where('gender', 'Male')->count();
            $femaleStudents = Student::where('gender', 'Female')->count();
            $teachersCount = User::where('role', 'Instructor')->count();
            $parentsCount = User::where('role', 'Parent')->count();

            if ($studentsCount === 0 && $teachersCount === 0 && $parentsCount === 0) {
                return response()->json(['message' => 'No data available yet.'], 200);
            }

            return response()->json([
                'students' => $studentsCount,
                'maleStudents' => $maleStudents,
                'femaleStudents' => $femaleStudents,
                'teachers' => $teachersCount,
                'parents' => $parentsCount
            ], 200);

        } catch (\Exception $e) {
            Log::error('Dashboard summary error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching dashboard summary.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // GET /api/dashboard/attendance-summary
    public function attendanceSummary()
    {

        // Get the start (Monday) and end (Sunday) of the current week
    $startOfWeek = Carbon::now()->startOfWeek();
    $endOfWeek = Carbon::now()->endOfWeek();

    // Fetch attendance counts grouped by day and status
    $summary = Attendance::select(
            DB::raw('DAYNAME(date_of_absence) as day'),
            'status',
            DB::raw('COUNT(*) as count')
        )
        ->whereBetween('date_of_absence', [$startOfWeek, $endOfWeek])
        ->groupBy('day', 'status')
        ->orderByRaw('FIELD(day, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")')
        ->get()
        ->groupBy('day');

    // Restructure output for frontend
    $formatted = [];

    foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
        $dayData = $summary->get($day, collect());
        $formatted[$day] = [
            'present'   => $dayData->where('status', 'present')->sum('count'),
            'absent'    => $dayData->where('status', 'absent')->sum('count'),
         
        ];
    }

    return response()->json([
        'week_start' => $startOfWeek->toDateString(),
        'week_end' => $endOfWeek->toDateString(),
        'summary' => $formatted
    ]);

    }

    // GET /api/dashboard/notices
   public function notices()
{
    try {
        $notices = Notice::orderBy('created_at', 'desc')
            ->get(['notice_id', 'title','description', 'created_at'])
            ->map(function ($notice) {
                return [
                    'id' => $notice->notice_id, // ✅ matches migration
                    'title' => $notice->title,
                    'date' => $notice->created_at ? $notice->created_at->format('Y-m-d') : null,
                ];
            });

        if ($notices->isEmpty()) {
            return response()->json([
                'message' => 'No notices found.',
                'notices' => []
            ], 200);
        }

        return response()->json([
            'notices' => $notices,
           
        ], 200);

    } catch (\Exception $e) {
        Log::error('Notices fetch error: ' . $e->getMessage());

        return response()->json([
            'error' => 'Something went wrong while fetching notices.',
            'details' => $e->getMessage()
        ], 500);
    }
}

    // GET /api/dashboard/events
    public function events()
    {
        try {
            $events = Event::orderBy('date', 'asc')->get(['id','title','description','date']);
            
            // Return empty array with 200 status instead of 404
            if ($events->isEmpty()) {
                return response()->json([
                    'message' => 'No events found.',
                    'events' => []
                ], 200);
            }
            
            return response()->json([
                'events' => $events,
        
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Events fetch error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Something went wrong while fetching events.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // NEW: Debug endpoint to check database data
    public function debugData()
    {
        try {
            $data = [
                'attendance_count' => Attendance::count(),
                'attendance_records' => Attendance::limit(5)->get(),
                'notices_count' => Notice::count(),
                'notices_records' => Notice::limit(5)->get(),
                'events_count' => Event::count(),
                'events_records' => Event::limit(5)->get(),
            ];
            
            return response()->json($data, 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Debug failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}