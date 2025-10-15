<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';
    protected $primaryKey = 'id';

    protected $fillable = [
        'course_name',
        'course_code',
        'class_id',
        'instructor_id',
        'description',
        'credit_hours',
        'is_active',
        'metadata',
        'core'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'credit_hours' => 'integer'
    ];

    protected $with = ['class', 'instructor'];

    /**
     * The class this course belongs to
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id')
            ->withDefault(['class_name' => 'Unassigned Class']);
    }

    /**
     * The instructor assigned to this course
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id')
            ->where('role', 'instructor')
            ->withDefault(['name' => 'No Instructor Assigned']);
    }

    /**
     * Results for this course
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'course_id');
    }

    /**
     * Timetable entries for this course
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(Timetable::class, 'course_id');
    }

    /**
     * Get average score for this course
     */
    public function getAverageScoreAttribute(): ?float
    {
        return $this->results()->avg('score');
    }

    /**
     * Get total students enrolled through the class
     */
    public function getStudentCountAttribute(): int
    {
        return optional($this->class)->students()->count() ?? 0;
    }

    /**
     * Scope for active courses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for courses with assigned classes
     */
    public function scopeHasClass($query)
    {
        return $query->whereNotNull('class_id');
    }

    /**
     * Scope for searching courses by name or code
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('course_name', 'like', "%{$searchTerm}%")
              ->orWhere('course_code', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Get formatted metadata with defaults
     */
    public function getSyllabusAttribute()
    {
        return $this->metadata['syllabus'] ?? 'No syllabus available';
    }

    /**
     * Get textbooks from metadata
     */
    public function getTextbooksAttribute()
    {
        return $this->metadata['textbooks'] ?? [];
    }
}