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
        'activity_type', // e.g., 'Daily Activity', 'Quiz', 'Test'
        'title',
        'date',
        'score',
        'amount',
        'max_score', // Added field
        'remarks'    // Added field
    ];

    protected $casts = [
        'date' => 'date',
        'score' => 'float',
        'amount' => 'float',
        'max_score' => 'float'
    ];

    protected $appends = [
        'percentage',
        'grade',
        'formatted_date'
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
     * Calculate percentage score
     */
    public function getPercentageAttribute(): ?float
    {
        if (!$this->max_score || $this->max_score <= 0) {
            return null;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Calculate letter grade
     */
    public function getGradeAttribute(): ?string
    {
        if (!$this->percentage) {
            return null;
        }

        foreach ($this->gradeThresholds as $grade => $threshold) {
            if ($this->percentage >= $threshold) {
                return $grade;
            }
        }
        return 'F';
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('M d, Y');
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
        return $query->where('date', '>=', now()->subDays(30));
    }

    /**
     * Check if result is passing (customize threshold as needed)
     */
    public function isPassing(): bool
    {
        return $this->percentage >= 60; // D or above
    }
}