<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $primaryKey = 'notice_id';
    protected $fillable = ['notice_type', 'student_id', 'title', 'description'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
