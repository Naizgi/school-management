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
        'core'  // Added core attribute
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'credit_hours' => 'integer',
        'core' => 'boolean'  // Cast core as boolean
    ];

    protected $attributes = [
        'is_active' => true,
        'core' => false,  // Default value for core
        'credit_hours' => 1
    ];

    protected $with = ['class', 'instructor'];

    protected $appends = [
        'average_score',
        'student_count',
        'syllabus',
        'textbooks',
        'core_subject_name',
        'is_core_subject'
    ];

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
     * Course assignments relationship
     */
    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
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
     * Get core subject status
     */
    public function getIsCoreSubjectAttribute(): bool
    {
        return (bool) $this->core;
    }

    /**
     * Get formatted core subject name
     */
    public function getCoreSubjectNameAttribute(): string
    {
        return $this->core ? 'Core Subject' : 'Elective Subject';
    }

    /**
     * Get formatted metadata with defaults
     */
    public function getSyllabusAttribute(): string
    {
        return $this->metadata['syllabus'] ?? 'No syllabus available';
    }

    /**
     * Get textbooks from metadata
     */
    public function getTextbooksAttribute(): array
    {
        return $this->metadata['textbooks'] ?? [];
    }

    /**
     * Get course difficulty level from metadata
     */
    public function getDifficultyLevelAttribute(): string
    {
        return $this->metadata['difficulty_level'] ?? 'Intermediate';
    }

    /**
     * Get prerequisites from metadata
     */
    public function getPrerequisitesAttribute(): array
    {
        return $this->metadata['prerequisites'] ?? [];
    }

    /**
     * Get learning objectives from metadata
     */
    public function getLearningObjectivesAttribute(): array
    {
        return $this->metadata['learning_objectives'] ?? [];
    }

    /**
     * Get assessment methods from metadata
     */
    public function getAssessmentMethodsAttribute(): array
    {
        return $this->metadata['assessment_methods'] ?? ['Exams', 'Assignments', 'Projects'];
    }

    /**
     * Scope for active courses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for core subjects only
     */
    public function scopeCore($query)
    {
        return $query->where('core', true);
    }

    /**
     * Scope for elective subjects only
     */
    public function scopeElective($query)
    {
        return $query->where('core', false);
    }

    /**
     * Scope for courses with assigned classes
     */
    public function scopeHasClass($query)
    {
        return $query->whereNotNull('class_id');
    }

    /**
     * Scope for courses by grade level
     */
    public function scopeByGrade($query, $grade)
    {
        return $query->whereHas('class', function ($q) use ($grade) {
            $q->where('grade', $grade);
        });
    }

    /**
     * Scope for courses by instructor
     */
    public function scopeByInstructor($query, $instructorId)
    {
        return $query->where('instructor_id', $instructorId);
    }

    /**
     * Scope for searching courses by name or code
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('course_name', 'like', "%{$searchTerm}%")
              ->orWhere('course_code', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope for courses with credit hours range
     */
    public function scopeCreditHoursBetween($query, $min, $max)
    {
        return $query->whereBetween('credit_hours', [$min, $max]);
    }

    /**
     * Check if course has available slots for enrollment
     */
    public function hasAvailableSlots(): bool
    {
        if (!$this->class) {
            return true;
        }

        $currentStudents = $this->class->students()->count();
        $maxCapacity = $this->class->max_students ?? 30;

        return $currentStudents < $maxCapacity;
    }

    /**
     * Get available slots count
     */
    public function getAvailableSlotsAttribute(): int
    {
        if (!$this->class) {
            return 30; // Default capacity
        }

        $currentStudents = $this->class->students()->count();
        $maxCapacity = $this->class->max_students ?? 30;

        return max(0, $maxCapacity - $currentStudents);
    }

    /**
     * Get course completion percentage (for progress tracking)
     */
    public function getCompletionPercentageAttribute(): float
    {
        // This could be calculated based on syllabus completion
        // For now, return a default or calculate based on results
        $totalResults = $this->results()->count();
        $completedResults = $this->results()->whereNotNull('completed_at')->count();

        if ($totalResults === 0) {
            return 0.0;
        }

        return round(($completedResults / $totalResults) * 100, 2);
    }

    /**
     * Get course duration in weeks (from metadata)
     */
    public function getDurationWeeksAttribute(): int
    {
        return $this->metadata['duration_weeks'] ?? 16; // Default 16-week semester
    }

    /**
     * Get course type based on credit hours and core status
     */
    public function getCourseTypeAttribute(): string
    {
        if ($this->core) {
            return $this->credit_hours >= 3 ? 'Major Core' : 'General Core';
        } else {
            return $this->credit_hours >= 3 ? 'Major Elective' : 'General Elective';
        }
    }

    /**
     * Set metadata attribute with validation
     */
    public function setMetadataAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['metadata'] = json_encode($value);
        } else {
            $this->attributes['metadata'] = $value;
        }
    }

    /**
     * Add textbook to metadata
     */
    public function addTextbook(string $title, string $author, string $edition = '1st'): void
    {
        $metadata = $this->metadata ?? [];
        $textbooks = $metadata['textbooks'] ?? [];

        $textbooks[] = [
            'title' => $title,
            'author' => $author,
            'edition' => $edition,
            'added_at' => now()->toDateTimeString()
        ];

        $metadata['textbooks'] = $textbooks;
        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Add learning objective to metadata
     */
    public function addLearningObjective(string $objective): void
    {
        $metadata = $this->metadata ?? [];
        $objectives = $metadata['learning_objectives'] ?? [];

        $objectives[] = $objective;
        $metadata['learning_objectives'] = array_unique($objectives);

        $this->metadata = $metadata;
        $this->save();
    }

    /**
     * Mark as core subject
     */
    public function markAsCore(): void
    {
        $this->update(['core' => true]);
    }

    /**
     * Mark as elective subject
     */
    public function markAsElective(): void
    {
        $this->update(['core' => false]);
    }

    /**
     * Toggle core status
     */
    public function toggleCoreStatus(): void
    {
        $this->update(['core' => !$this->core]);
    }

    /**
     * Get similar courses (same class or similar credit hours)
     */
    public function similarCourses($limit = 5)
    {
        return self::where('id', '!=', $this->id)
            ->where(function($query) {
                $query->where('class_id', $this->class_id)
                      ->orWhereBetween('credit_hours', [$this->credit_hours - 1, $this->credit_hours + 1]);
            })
            ->where('is_active', true)
            ->limit($limit)
            ->get();
    }

    /**
     * Get course statistics
     */
    public function getStatisticsAttribute(): array
    {
        return [
            'total_students' => $this->student_count,
            'average_score' => $this->average_score,
            'available_slots' => $this->available_slots,
            'completion_percentage' => $this->completion_percentage,
            'timetable_slots' => $this->timetableSlots()->count(),
            'course_assignments' => $this->courseAssignments()->count(),
            'is_core_subject' => $this->is_core_subject,
            'course_type' => $this->course_type
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate course code if not provided
        static::creating(function ($course) {
            if (empty($course->course_code)) {
                $course->course_code = static::generateCourseCode($course->course_name);
            }
        });

        // Update related course assignments when course is deactivated
        static::updating(function ($course) {
            if ($course->isDirty('is_active') && !$course->is_active) {
                $course->courseAssignments()->update(['is_active' => false]);
            }
        });
    }

    /**
     * Generate course code from course name
     */
    protected static function generateCourseCode(string $courseName): string
    {
        $words = explode(' ', $courseName);
        $code = '';

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $code .= strtoupper($word[0]);
            }
        }

        // Add random numbers to ensure uniqueness
        $code .= rand(100, 999);

        return $code;
    }
}