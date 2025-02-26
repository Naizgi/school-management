<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $primaryKey = 'attendance_id';
    protected $fillable = ['student_id', 'date_of_absence', 'reason'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
