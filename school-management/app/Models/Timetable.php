<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeTable extends Model
{
    use HasFactory;

    protected $primaryKey = 'timetable_id';
    protected $fillable = ['class_id', 'day_of_week', 'course_id', 'timeslot_id'];

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
