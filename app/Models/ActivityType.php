<?php
// app/Models/ActivityType.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityType extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'activity_type_id';
    
    protected $fillable = [
        'activity_type',
        'description',
        'default_weight',
        'grade_level',
        'semester_pattern',
        'is_active'
    ];

    protected $casts = [
        'default_weight' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationship with results
    public function results()
    {
        return $this->hasMany(Result::class, 'activity_type', 'activity_type');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByGrade($query, $grade)
    {
        return $query->where(function($q) use ($grade) {
            $q->whereNull('grade_level')
              ->orWhere('grade_level', 'like', "%{$grade}%");
        });
    }

    public function scopeBySemester($query, $semester)
    {
        return $query->where(function($q) use ($semester) {
            $q->whereNull('semester_pattern')
              ->orWhere('semester_pattern', $semester);
        });
    }
}