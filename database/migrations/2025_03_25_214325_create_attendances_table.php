<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id('attendance_id');
            
            // Core relationships
            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Related student record');
                  
            $table->foreignId('course_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->comment('Specific course if applicable');

            // Attendance details
            $table->date('date_of_absence');
            $table->enum('status', [
                'present', 
                'absent', 
                'late', 
                'excused', 
                'suspended'
            ])->default('present');
            
            $table->enum('period', [
                'morning', 
                'afternoon', 
                'full_day'
            ])->default('full_day');
            
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            
            // Verification
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Staff who recorded this');
                  
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Staff who verified this');

            // Indexes
            $table->index('student_id');
            $table->index('date_of_absence');
            $table->index(['student_id', 'date_of_absence']);
            
            $table->timestamps();
            $table->softDeletes();
        });

        // For schools with strict attendance policies
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(
                ['student_id', 'date_of_absence', 'period'],
                'unique_attendance_record'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};