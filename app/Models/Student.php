<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_phone_number',
        'name',
        'class_id', 
        'roll_number',
        'academic_year',
        'date_of_admission',
        'father_name',
        'mother_name',
        'date_of_birth',
        'address',
        'profile_picture'
    ];

    protected $casts = [
        'date_of_admission' => 'date',
        'date_of_birth' => 'date'
    ];

    protected $appends = [
        'age',
        'profile_picture_url',
        'formatted_date_of_birth',
        'formatted_date_of_admission'
    ];

    /**
     * Relationship with Parent via phone_number
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_phone_number', 'phone_number')
            ->where('role', 'parent');
    }

    /**
     * Relationship with Class
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Relationship with Results
     */
   public function results()
    {
    return $this->hasMany(Result::class, 'student_id')
                ->select('id', 'student_id', 'course_id', 'semester', 'activity_type', 'title', 'assessment_date', 'score', 'max_score', 'percentage', 'comments', 'graded_by', 'created_at', 'updated_at');
    }


    /**
     * Relationship with Attendance
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    /**
     * Relationship with Fees
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'student_id');
    }

    /**
     * Calculated age attribute
     */
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    /**
     * Profile picture URL accessor
     */
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return asset('images/default-avatar.png');
        }

        // If it's already a full URL (from external source), return as is
        if (filter_var($this->profile_picture, FILTER_VALIDATE_URL)) {
            return $this->profile_picture;
        }

        // If it's a storage path, generate the URL
        return Storage::disk('public')->url($this->profile_picture);
    }

    /**
     * Formatted date of birth
     */
    public function getFormattedDateOfBirthAttribute(): string
    {
        return $this->date_of_birth->format('M d, Y');
    }

    /**
     * Formatted date of admission
     */
    public function getFormattedDateOfAdmissionAttribute(): string
    {
        return $this->date_of_admission->format('M d, Y');
    }

    /**
     * Check if student has a profile picture
     */
    public function hasProfilePicture(): bool
    {
        return !empty($this->profile_picture) && $this->profile_picture !== 'defaults/default-profile.png';
    }

    /**
     * Get current academic status based on admission date
     */
    public function getAcademicStatusAttribute(): string
    {
        $currentYear = now()->year;
        $admissionYear = $this->date_of_admission->year;
        
        $yearsSinceAdmission = $currentYear - $admissionYear;
        
        if ($yearsSinceAdmission == 0) {
            return 'New Student';
        } elseif ($yearsSinceAdmission == 1) {
            return 'Returning Student';
        } else {
            return 'Senior Student';
        }
    }

    /**
     * Scope for students by class
     */
    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope for students by academic year
     */
    public function scopeByAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope for active students (not transferred/graduated)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Update profile picture and handle file storage
     */
    public function updateProfilePicture($file): bool
    {
        try {
            // Delete old profile picture if it exists
            if ($this->hasProfilePicture()) {
                Storage::disk('public')->delete($this->profile_picture);
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = 'student_' . $this->id . '_' . Str::random(10) . '.' . $extension;
            $path = 'students/profiles/' . $filename;

            // Store the file
            $file->storeAs('students/profiles', $filename, 'public');

            // Update the model
            $this->update(['profile_picture' => $path]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update profile picture: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(): bool
    {
        try {
            if ($this->hasProfilePicture()) {
                Storage::disk('public')->delete($this->profile_picture);
                $this->update(['profile_picture' => null]);
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete profile picture: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student's full information for API responses
     */
    public function toArrayWithDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roll_number' => $this->roll_number,
            'class' => $this->class ? $this->class->only('id', 'class_name', 'section') : null,
            'parent_phone_number' => $this->parent_phone_number,
            'parent' => $this->parent ? $this->parent->only('name', 'email', 'phone_number') : null,
            'academic_year' => $this->academic_year,
            'date_of_birth' => $this->formatted_date_of_birth,
            'age' => $this->age,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'address' => $this->address,
            'profile_picture' => $this->profile_picture_url,
            'academic_status' => $this->academic_status,
            'date_of_admission' => $this->formatted_date_of_admission,
        ];
    }

    public function attendance()
{
    return $this->hasMany(Attendance::class);
}


    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Generate roll number if not provided
        static::creating(function ($student) {
            if (empty($student->roll_number)) {
                $student->roll_number = static::generateRollNumber($student->class_id);
            }
        });

        // Delete associated files when student is deleted
        static::deleting(function ($student) {
            if ($student->hasProfilePicture()) {
                Storage::disk('public')->delete($student->profile_picture);
            }
        });
    }

    /**
     * Generate unique roll number
     */
    protected static function generateRollNumber($classId): string
    {
        $class = ClassModel::find($classId);
        $classCode = $class ? Str::upper(Str::substr($class->class_name, 0, 3)) : 'GEN';
        $year = now()->format('y');
        $sequence = static::where('class_id', $classId)->count() + 1;

        return $classCode . $year . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
}