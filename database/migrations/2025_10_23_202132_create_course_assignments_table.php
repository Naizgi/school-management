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
        // Drop the table if it exists (with any structure)
        Schema::dropIfExists('course_assignments');
        
        // Create the table with correct structure
        Schema::create('course_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_id'); // Represents class + section
            $table->string('academic_year');
            $table->string('semester')->nullable();
            $table->integer('max_students')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
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

            // Unique constraint
            $table->unique([
                'instructor_id', 
                'course_id', 
                'class_id', 
                'academic_year'
            ], 'unique_course_assignment');
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