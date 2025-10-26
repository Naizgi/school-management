<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if all required tables exist
        if (!Schema::hasTable('users') || !Schema::hasTable('courses') || !Schema::hasTable('classes')) {
            throw new Exception('Required tables (users, courses, classes) must exist before creating course_assignments table.');
        }

        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            
            // Use unsignedBigInteger for more control
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id'); // This represents both class and section
            
            $table->string('academic_year');
            $table->string('semester')->nullable();
            $table->integer('max_students')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Add foreign keys
            $table->foreign('instructor_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('course_id')
                  ->references('id')
                  ->on('courses')
                  ->onDelete('cascade');

            $table->foreign('class_id')
                  ->references('id')
                  ->on('classes')
                  ->onDelete('cascade');

            // Unique constraint to prevent duplicate assignments
            // Since sections are part of classes table, we only need class_id
            $table->unique([
                'instructor_id', 
                'course_id', 
                'class_id', 
                'academic_year'
            ], 'unique_course_assignment');

            // Add indexes for better performance
            $table->index(['class_id']);
            $table->index(['instructor_id', 'academic_year']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_assignments');
    }
};