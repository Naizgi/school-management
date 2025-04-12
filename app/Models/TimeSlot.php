<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;
    protected $table = 'timeslots';
    protected $primaryKey = 'id';
    protected $fillable = ['start_time', 'end_time'];
}
