<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Library extends Model
{
    //
    use HasFactory;

    protected $primaryKey = 'book_id';
    protected $fillable = ['title', 'author', 'category', 'description', 'cover_image', 'file_url'];

}
