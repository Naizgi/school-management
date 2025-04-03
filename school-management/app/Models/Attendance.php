<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date_of_absence',
        'reason'
    ];

    protected $casts = [
        'date_of_absence' => 'date'
    ];

    /**
     * Relationship with Student
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope for absences on a specific date
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|Carbon $date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->where('date_of_absence', Carbon::parse($date)->format('Y-m-d'));
    }

    /**
     * Scope for recent absences (last 30 days)
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeRecent($query)
    {
        return $query->where('date_of_absence', '>=', now()->subDays(30));
    }

    /**
     * Get formatted absence date
     * @return string
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date_of_absence->format('M d, Y');
    }

    /**
     * Check if absence is from today
     * @return bool
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->date_of_absence->isToday();
    }
}