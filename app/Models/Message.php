<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Message extends Model
{
    use HasFactory;

    protected $primaryKey = 'message_id';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'is_read', // Added read status tracking
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'is_read' => 'boolean'
    ];

    protected $appends = [
        'formatted_time',
        'is_recent'
    ];

    /**
     * The sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id')
            ->withDefault([
                'name' => 'Deleted User',
                'profile_picture' => null
            ]);
    }

    /**
     * The receiver of the message
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id')
            ->withDefault([
                'name' => 'Deleted User',
                'profile_picture' => null
            ]);
    }

    /**
     * Get formatted timestamp (e.g., "Today at 3:45 PM")
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->timestamp->format('M j, Y \a\t g:i A');
    }

    /**
     * Check if message was sent recently (last 5 minutes)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->timestamp->diffInMinutes() < 5;
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): bool
    {
        return $this->update(['is_read' => true]);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for messages between two users
     */
    public function scopeBetweenUsers(Builder $query, int $user1, int $user2): Builder
    {
        return $query->where(function($q) use ($user1, $user2) {
            $q->where('sender_id', $user1)
              ->where('receiver_id', $user2);
        })->orWhere(function($q) use ($user1, $user2) {
            $q->where('sender_id', $user2)
              ->where('receiver_id', $user1);
        });
    }

    /**
     * Scope for messages sent after a certain date
     */
    public function scopeAfter(Builder $query, Carbon $date): Builder
    {
        return $query->where('timestamp', '>', $date);
    }
}