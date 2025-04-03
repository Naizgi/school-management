<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $fillable = [
        'title',
        'date',
        'description'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    protected $appends = [
        'formatted_date',
        'is_upcoming'
    ];

    /**
     * Gallery images for this event
     */
    public function gallery(): HasMany
    {
        return $this->hasMany(Gallery::class);
    }

    /**
     * Get formatted date (e.g., "March 15, 2023")
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('F j, Y');
    }

    /**
     * Check if event is upcoming (within next 7 days)
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->date->between(
            now(),
            now()->addDays(7)
        );
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())
                    ->where('date', '<=', now()->addDays(7))
                    ->orderBy('date');
    }

    /**
     * Scope for past events
     */
    public function scopePast($query)
    {
        return $query->where('date', '<', now())
                    ->orderByDesc('date');
    }

    /**
     * Scope for events on specific date
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', Carbon::parse($date));
    }
}