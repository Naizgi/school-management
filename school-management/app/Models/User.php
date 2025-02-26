<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    protected $fillable = [
        'user_name', 'phone_number', 'password', 'role',
        'profile_picture', 'first_name', 'last_name'
    ];

    public function parent()
    {
        return $this->hasOne(Parent::class, 'user_id');
    }

    public function instructor()
    {
        return $this->hasOne(Instructor::class, 'user_id');
    }
}
