<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'course_id',
        'class_id',
        'section_id',
        'academic_year',
        'semester',
        'max_students',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_students' => 'integer'
    ];

    // Relationships
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'course_assignment_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    public function scopeForClassSection($query, $classId, $sectionId)
    {
        return $query->where('class_id', $classId)->where('section_id', $sectionId);
    }
}