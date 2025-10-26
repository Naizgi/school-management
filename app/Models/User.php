<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_name',
        'phone_number',
        'password',
        'role', // 'parent', 'instructor', 'admin'
        'profile_picture',
        'first_name',
        'last_name',
        'email',
        'last_login_at',
        'status' // 'active', 'inactive', 'suspended'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'profile_picture_url',
        'role_display_name'
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'name' => $this->full_name,
            'profile_picture' => $this->profile_picture_url
        ];
    }

    // Relationships
    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }
    
    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'instructor_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return asset('images/default-avatar.png');
        }
        
        // If it's already a full URL, return as is
        if (filter_var($this->profile_picture, FILTER_VALIDATE_URL)) {
            return $this->profile_picture;
        }

        // If it's a storage path, generate the URL
        return Storage::disk('public')->url($this->profile_picture);
    }

    public function getRoleDisplayNameAttribute(): string
    {
        return match($this->role) {
            'admin' => 'Administrator',
            'instructor' => 'Teacher',
            'parent' => 'Parent',
            default => ucfirst($this->role)
        };
    }

    // Scopes
    public function scopeParents($query)
    {
        return $query->where('role', 'parent');
    }

    public function scopeInstructors($query)
    {
        return $query->where('role', 'instructor');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone_number', 'like', "%{$search}%")
              ->orWhere('user_name', 'like', "%{$search}%");
        });
    }

    // Business Logic
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    public function isParent(): bool
    {
        return $this->role === 'parent';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
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
            $filename = 'user_' . $this->id . '_' . Str::random(10) . '.' . $extension;
            $path = 'users/profiles/' . $filename;

            // Store the file
            $file->storeAs('users/profiles', $filename, 'public');

            // Update the model
            $this->update(['profile_picture' => $path]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update user profile picture: ' . $e->getMessage());
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
            \Log::error('Failed to delete user profile picture: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has a custom profile picture
     */
    public function hasProfilePicture(): bool
    {
        return !empty($this->profile_picture) && !Str::contains($this->profile_picture, 'default-avatar');
    }

    /**
     * Get user's students (for parents)
     */
    public function getStudents()
    {
        if (!$this->isParent()) {
            return collect();
        }

        return Student::where('parent_phone_number', $this->phone_number)->get();
    }

    /**
     * Get user's assigned courses (for instructors)
     */
    public function getAssignedCourses()
    {
        if (!$this->isInstructor()) {
            return collect();
        }

        return $this->courseAssignments()->with('course')->get()->pluck('course');
    }

    /**
     * Get user statistics for dashboard
     */
    public function getDashboardStatistics(): array
    {
        return match($this->role) {
            'parent' => $this->getParentStatistics(),
            'instructor' => $this->getInstructorStatistics(),
            'admin' => $this->getAdminStatistics(),
            default => []
        };
    }

    protected function getParentStatistics(): array
    {
        $students = $this->getStudents();
        
        return [
            'total_students' => $students->count(),
            'active_students' => $students->where('status', 'active')->count(),
            'total_classes' => $students->pluck('class_id')->unique()->count(),
        ];
    }

    protected function getInstructorStatistics(): array
    {
        $courses = $this->getAssignedCourses();
        
        return [
            'total_courses' => $courses->count(),
            'active_courses' => $courses->where('status', 'active')->count(),
            'total_students' => 0, // You might want to calculate this based on enrollments
        ];
    }

    protected function getAdminStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'total_students' => Student::count(),
            'total_instructors' => User::instructors()->count(),
            'total_parents' => User::parents()->count(),
        ];
    }

    /**
     * Get user information for API responses
     */
    public function toArrayWithDetails(): array
    {
        $baseData = [
            'id' => $this->id,
            'user_name' => $this->user_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $this->role,
            'role_display_name' => $this->role_display_name,
            'profile_picture' => $this->profile_picture_url,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->format('M d, Y H:i'),
            'created_at' => $this->created_at->format('M d, Y'),
        ];

        // Add role-specific data
        if ($this->isParent() && $this->guardian) {
            $baseData['guardian'] = $this->guardian;
            $baseData['students'] = $this->getStudents()->map->toArrayWithDetails();
        }

        if ($this->isInstructor() && $this->instructor) {
            $baseData['instructor'] = $this->instructor;
            $baseData['assigned_courses'] = $this->getAssignedCourses();
        }

        return $baseData;
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Generate username if not provided
        static::creating(function ($user) {
            if (empty($user->user_name)) {
                $user->user_name = static::generateUsername($user->first_name, $user->last_name);
            }
        });

        // Set default status
        static::creating(function ($user) {
            if (empty($user->status)) {
                $user->status = 'active';
            }
        });

        // Delete associated files when user is deleted
        static::deleting(function ($user) {
            if ($user->hasProfilePicture()) {
                Storage::disk('public')->delete($user->profile_picture);
            }
        });
    }

    /**
     * Generate unique username
     */
    protected static function generateUsername($firstName, $lastName): string
    {
        $baseUsername = Str::lower($firstName . '.' . $lastName);
        $username = $baseUsername;
        $counter = 1;

        while (static::where('user_name', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }
}