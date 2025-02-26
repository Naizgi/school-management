<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';
    protected $fillable = [
        'parent_id', 'name', 'class_id', 'roll_number', 'academic_year',
        'date_of_admission', 'father_name', 'mother_name', 'date_of_birth',
        'age', 'address', 'profile_picture'
    ];

    public function parent()
    {
        return $this->belongsTo(Parent::class, 'parent_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
