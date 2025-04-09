<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'class_id' // Nullable for general instructors
    ];

    protected $with = ['user']; // Eager load user by default

    /**
     * User account relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->where('role', 'instructor')
            ->withDefault(['name' => 'Deleted User']);
    }

    /**
     * Assigned class relationship (nullable)
     */
    public function assignedClass(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id')
            ->withDefault(['class_name' => 'No Class Assigned']);
    }

    /**
     * Courses taught by this instructor
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /**
     * Get the homeroom class (if assigned)
     */
    public function homeroomClass(): HasOne
    {
        return $this->hasOne(ClassModel::class, 'homeroom_teacher_id');
    }

    /**
     * Get total students under this instructor
     */
    public function getStudentCountAttribute(): int
    {
        return $this->assignedClass ? $this->assignedClass->students()->count() : 0;
    }

    /**
     * Scope for instructors with class assignments
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('class_id');
    }

    /**
     * Scope for searching instructors by name
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where('name', 'like', "%{$searchTerm}%");
    }
}