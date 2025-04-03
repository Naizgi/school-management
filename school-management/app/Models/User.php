<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'email', // Added for better authentication
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
        'profile_url'
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
            'name' => $this->full_name
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

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getProfileUrlAttribute(): ?string
    {
        if (!$this->profile_picture) {
            return null;
        }
        
        return filter_var($this->profile_picture, FILTER_VALIDATE_URL) 
            ? $this->profile_picture
            : asset('storage/'.$this->profile_picture);
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

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }
}