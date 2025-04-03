<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_phone_number', // Kept as original
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

    // Relationship with Parent via phone_number
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_phone_number', 'phone_number')
            ->where('role', 'parent');
    }

    // Relationship with Class
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    // Relationship with Results
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    // Relationship with Attendance
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Calculated age attribute
    public function getAgeAttribute(): int
    {
        return Carbon::parse($this->date_of_birth)->age;
    }

    // Profile picture URL accessor
    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }
        return str_starts_with($this->profile_picture, 'http') 
            ? $this->profile_picture 
            : asset('storage/' . $this->profile_picture);
    }
}