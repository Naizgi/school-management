<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';
    
    protected $fillable = [
        'class_name',
        'section',
        'academic_year',
        'description',
        'homeroom_teacher_id',
        'secondary_teacher_id',
        'max_students',
        'is_active'
    ];

    protected $attributes = [
        'is_active' => true,
        'max_students' => 30
    ];

    protected $with = ['homeroomTeacher', 'secondaryTeacher'];

    /**
     * Homeroom teacher relationship
     */
  

    /**
     * Secondary teacher relationship
     */
    public function secondaryTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secondary_teacher_id')
            ->where('role', 'instructor')
            ->withDefault();
    }

    /**
     * Students in this class
     */
  // In app/Models/ClassModel.php

// Students relationship (using class_id)
public function students(): HasMany
{
    return $this->hasMany(Student::class, 'class_id')
        ->select(['id', 'name', 'class_id', 'roll_number']);
}

// Courses relationship
public function courses(): HasMany
{
    return $this->hasMany(Course::class, 'class_id')
        ->select(['id', 'course_name', 'class_id', 'description']);
}

// Homeroom teacher relationship
public function homeroomTeacher(): BelongsTo
{
    return $this->belongsTo(User::class, 'homeroom_teacher_id')
        ->select(['id', 'user_name', 'email']) // Adjust to your User model
        ->where('role', 'instructor')
        ->withDefault(['username' => 'No Teacher']);
}/**
     * Class timetable
     */
    public function timetable(): HasOne
    {
        return $this->hasOne(Timetable::class);
    }

    /**
     * Get total students count
     */
    public function getStudentCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get active courses count
     */
    public function getActiveCoursesCountAttribute(): int
    {
        return $this->courses()->active()->count();
    }

    /**
     * Scope for classes with homeroom teachers
     */
    public function scopeWithTeacher($query)
    {
        return $query->whereNotNull('homeroom_teacher_id');
    }

    /**
     * Scope for searching classes
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('class_name', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%");
    }

    /**
     * Scope for active classes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}