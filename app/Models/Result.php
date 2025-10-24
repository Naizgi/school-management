<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Result extends Model
{
    use HasFactory;

    protected $primaryKey = 'result_id';

    protected $fillable = [
        'student_id',
        'course_id',
        'semester',
        'activity_type', // Now references activity_types table
        'title',
        'assessment_date',
        'score',
        'amount',
        'max_score',
        'remarks',
        'weight', // Added: Use activity type's default_weight or override
        'percentage', // Added: Auto-calculated percentage
        'published_at', // Added: When results were released
        'graded_by', // Added: Who graded this
        'comments', // Added: Additional comments
        'rubric_data' // Added: Detailed grading criteria JSON
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'published_at' => 'datetime',
        'score' => 'float',
        'amount' => 'float',
        'max_score' => 'float',
        'weight' => 'float',
        'percentage' => 'float',
        'rubric_data' => 'array'
    ];

    protected $appends = [
        'calculated_percentage', // Renamed to avoid conflict with database column
        'grade',
        'formatted_assessment_date',
        'is_published'
    ];

    // Grade thresholds (customize as needed)
    protected $gradeThresholds = [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'F' => 0
    ];

    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)
            ->withDefault([
                'name' => 'Unknown Student'
            ]);
    }

    /**
     * Course relationship
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)
            ->withDefault([
                'course_name' => 'Unknown Course'
            ]);
    }

    /**
     * Activity Type relationship
     */
    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type', 'activity_type')
            ->withDefault([
                'activity_type' => 'Unknown Activity',
                'default_weight' => 0,
                'description' => 'No description available'
            ]);
    }

    /**
     * Calculate percentage score (uses database column if exists, otherwise calculates)
     */
    public function getCalculatedPercentageAttribute(): ?float
    {
        // Use stored percentage if available
        if ($this->percentage !== null) {
            return $this->percentage;
        }
        
        // Calculate if max_score is available
        if (!$this->max_score || $this->max_score <= 0) {
            return null;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Get weight (uses activity type's default weight if not specified)
     */
    public function getEffectiveWeightAttribute(): float
    {
        return $this->weight ?? $this->activityType->default_weight ?? 0;
    }

    /**
     * Calculate letter grade
     */
    public function getGradeAttribute(): ?string
    {
        $percentage = $this->calculated_percentage;
        
        if (!$percentage) {
            return null;
        }

        foreach ($this->gradeThresholds as $grade => $threshold) {
            if ($percentage >= $threshold) {
                return $grade;
            }
        }
        return 'F';
    }

    /**
     * Get formatted assessment date
     */
    public function getFormattedAssessmentDateAttribute(): string
    {
        return $this->assessment_date->format('M d, Y');
    }

    /**
     * Check if result is published
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }

    /**
     * Check if result is passing (customize threshold as needed)
     */
    public function isPassing(): bool
    {
        return $this->calculated_percentage >= 60; // D or above
    }

    /**
     * Scope for results by semester
     */
    public function scopeForSemester(Builder $query, string $semester): Builder
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope for results by activity type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope for published results
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope for unpublished results
     */
    public function scopeUnpublished(Builder $query): Builder
    {
        return $query->whereNull('published_at')
                    ->orWhere('published_at', '>', now());
    }

    /**
     * Scope for results above minimum score
     */
    public function scopeAboveScore(Builder $query, float $score): Builder
    {
        return $query->where('score', '>=', $score);
    }

    /**
     * Scope for recent results (last 30 days)
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('assessment_date', '>=', now()->subDays(30));
    }

    /**
     * Boot method for auto-calculating percentage
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($result) {
            // Auto-calculate percentage if not set
            if ($result->max_score && $result->max_score > 0 && $result->score !== null) {
                $result->percentage = round(($result->score / $result->max_score) * 100, 2);
            }
            
            // Set default weight from activity type if not specified
            if ($result->weight === null && $result->activityType) {
                $result->weight = $result->activityType->default_weight;
            }
        });
    }
}