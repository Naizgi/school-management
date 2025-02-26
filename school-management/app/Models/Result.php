<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $primaryKey = 'result_id';
    protected $fillable = [
        'student_id', 'course_id', 'semester', 'activity_type',
        'title', 'date', 'score', 'amount'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
