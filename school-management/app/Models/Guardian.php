<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model
{
    use HasFactory;

    protected $table = 'parents'; // Keeps existing table name
    protected $primaryKey = 'parent_id'; // Maintains your custom PK
    protected $fillable = ['user_id']; // Mass assignable fields

    /**
     * Relationship with User
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->withDefault(); // Prevents null reference errors
    }

    /**
     * Relationship with Students
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_phone_number', 'parent_phone_number');
    }

    /**
     * Get guardian's full name through user relationship
     * @return string|null
     */
    public function getFullNameAttribute(): ?string
    {
        return optional($this->user)->full_name;
    }

    /**
     * Get guardian's contact number through user relationship
     * @return string|null
     */
    public function getContactNumberAttribute(): ?string
    {
        return optional($this->user)->phone_number;
    }
}