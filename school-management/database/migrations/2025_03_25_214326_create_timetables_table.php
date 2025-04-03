<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id('timetable_id'); // Matches your $primaryKey
            
            // Core scheduling
            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete()
                  ->comment('Associated class');
                  
            $table->foreignId('course_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Scheduled course');
                  
            $table->foreignId('timeslot_id')
                  ->constrained('timeslots')
                  ->cascadeOnDelete()
                  ->comment('Time period reference');
            
            // Schedule details
            $table->enum('day_of_week', [
                'Monday',
                'Tuesday', 
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday'
            ])->comment('Matches DAYS_OF_WEEK constant');
            
            // Active period
            $table->date('start_date')->nullable()
                  ->comment('When this schedule becomes active');
            $table->date('end_date')->nullable()
                  ->comment('When this schedule expires');
            $table->boolean('is_active')->default(true)
                  ->comment('Manual activation control');
            
            // System
            $table->timestamps();
            $table->softDeletes(); // Supports model queries

            // Indexes for model scopes
            $table->index('is_active'); // For active() scope
            $table->index('day_of_week'); // For onDay() scope
            $table->index('class_id'); // For forClass() scope
            $table->index(['start_date', 'end_date']); // For current() scope
        });

        // For MySQL 8.0+ (improves day_of_week searches)
        DB::statement(
            'CREATE INDEX timetable_day_week_index ON timetables (day_of_week) USING HASH'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('timetables');
    }
};