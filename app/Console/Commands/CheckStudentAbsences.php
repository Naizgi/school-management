<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceAlert;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\StudentAbsentNotification;
use Illuminate\Support\Facades\Log;

class CheckStudentAbsences extends Command
{
    protected $signature = 'attendance:check-absences';
    protected $description = 'Check for students absent for 5 consecutive days and notify teachers/admins';

    public function handle()
    {
        $students = Student::all();

        foreach ($students as $student) {
            $absentCount = Attendance::where('student_id', $student->id)
                ->where('status', 'Absent')
                ->orderBy('date', 'desc')
                ->take(5)
                ->count();

            if ($absentCount >= 5) {
                $alreadyAlerted = AttendanceAlert::where('student_id', $student->id)
                    ->where('alert_type', 'absence_5_days')
                    ->where('is_confirmed', false)
                    ->exists();

                if (!$alreadyAlerted) {
                    $message = "{$student->name} has been absent for 5 consecutive days.";

                    AttendanceAlert::create([
                        'student_id' => $student->id,
                        'alert_type' => 'absence_5_days',
                        'message' => $message,
                    ]);

                    // Find recipients
                    $teacher = User::where('role', 'Teacher')
                        ->where('class_id', $student->class_id)
                        ->first();

                    $admins = User::where('role', 'Admin')->get();

                    Notification::send([$teacher, ...$admins], new StudentAbsentNotification($student, $message));

                    Log::info("Alert sent for student: {$student->name}");
                }
            }
        }

        $this->info('5-day absence check completed.');
    }
}
