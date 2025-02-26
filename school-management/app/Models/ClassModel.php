<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $primaryKey = 'class_id';
    protected $fillable = ['class_name', 'homeroom_teacher_id'];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'homeroom_teacher_id');
    }
}
