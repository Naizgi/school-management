<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Notice extends Model
{
    use HasFactory;

    protected $primaryKey = 'notice_id';
    protected $fillable = [
        'notice_type',
        'student_id', // Nullable for general notices
        'title',
        'description',
        'is_published',
        'expiry_date'
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'expiry_date' => 'date',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    protected $appends = [
        'is_active',
        'short_description'
    ];

    /**
     * Student relationship (for specific notices)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)
            ->withDefault([
                'name' => 'All Students'
            ]);
    }

    /**
     * Class relationship (derived through student)
     */
    public function class()
    {
        return optional($this->student)->class();
    }

    /**
     * Check if notice is active (published and not expired)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->is_published && 
               (!$this->expiry_date || $this->expiry_date->isFuture());
    }

    /**
     * Get truncated description (first 100 chars)
     */
    public function getShortDescriptionAttribute(): string
    {
        return str_limit($this->description, 100);
    }

    /**
     * Scope for published notices
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for active notices (published and not expired)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->published()
            ->where(function($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scope for general notices (not student-specific)
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('student_id');
    }

    /**
     * Scope for student-specific notices
     */
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Publish the notice
     */
    public function publish(): bool
    {
        return $this->update(['is_published' => true]);
    }

    /**
     * Unpublish the notice
     */
    public function unpublish(): bool
    {
        return $this->update(['is_published' => false]);
    }
}