<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'image_url',
        'caption',
        'is_featured',
        'storage_path',
        'uploaded_by',
    ];

    /**
     * Relationship: Gallery belongs to an Event
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relationship: Gallery image was uploaded by a User
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope: Featured images only
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Accessor: Get the full URL of the image
     */
    public function getFullImageUrlAttribute()
    {
        return $this->image_url ?? asset('storage/' . $this->storage_path);
    }
}
