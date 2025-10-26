<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;

class StudentAbsentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $student;
    protected $message;

    public function __construct($student, $message)
    {
        $this->student = $student;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'message' => $this->message,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'message' => $this->message,
        ]);
    }
}
