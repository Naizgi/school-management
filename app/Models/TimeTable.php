<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TimeTable extends Model
{
    use HasFactory;

    protected $table = 'timetables';

    protected $primaryKey = 'id';
    protected $fillable = [
        'class_id',
        'day_of_week', // e.g., 'Monday', 'Tuesday'
        'course_id',
        'timeslot_id',
        'is_active',   // Added field
        'start_date',  // Added field
        'end_date'     // Added field
    ];

    protected $casts = [
        'day_of_week' => 'string',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    protected $appends = [
        'schedule_period',
        'is_current'
    ];

    // Days of week constants
    public const DAYS_OF_WEEK = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday'
    ];

    /**
     * Class relationship
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class)
            ->withDefault([
                'class_name' => 'Unassigned Class'
            ]);
    }

    /**
     * Course relationship
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class)
            ->withDefault([
                'course_name' => 'Unassigned Course'
            ]);
    }

    /**
     * Timeslot relationship
     */
    public function timeslot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class)
            ->withDefault([
                'start_time' => '00:00',
                'end_time' => '00:00'
            ]);
    }

    /**
     * Get formatted schedule period (e.g., "Jan 1 - Dec 31")
     */
    public function getSchedulePeriodAttribute(): string
    {
        if (!$this->start_date || !$this->end_date) {
            return 'Ongoing';
        }
        return $this->start_date->format('M j').' - '.$this->end_date->format('M j, Y');
    }

    /**
     * Check if timetable is currently active
     */
    public function getIsCurrentAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }
        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope for active timetables
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for current timetables (active and within date range)
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->active()
            ->where(function($q) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope for timetables on specific day
     */
    public function scopeOnDay(Builder $query, string $day): Builder
    {
        return $query->where('day_of_week', ucfirst(strtolower($day)));
    }

    /**
     * Scope for timetables for specific class
     */
    public function scopeForClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Activate the timetable
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the timetable
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }
}