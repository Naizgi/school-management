<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id('id'); // Matches your $primaryKey
            
            // Core fields from model
            $table->string('course_name');
            $table->foreignId('class_id')
                  ->nullable()
                  ->constrained('classes')
                  ->nullOnDelete();
            
            // New fields to support model methods
            $table->foreignId('instructor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Direct instructor reference');
                  
            $table->string('course_code', 20)->unique()->nullable()
                  ->comment('Short identifier e.g. MATH101');
            
            // Enhanced fields
            $table->text('description')->nullable();
            $table->integer('credit_hours')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable()
                  ->comment('Syllabus, textbooks etc.');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes(); // Supports model queries
            
            // Indexes for model scopes
            $table->index('class_id'); // For active() scope
            $table->fullText('course_name'); // For search() scope
        });

        // Add this if you need to backfill instructor_id from homeroom teachers
        DB::statement('
            UPDATE courses c
            JOIN classes cl ON c.class_id = cl.id
            SET c.instructor_id = cl.homeroom_teacher_id
            WHERE c.instructor_id IS NULL
        ');
    }

    public function down()
    {
        Schema::dropIfExists('courses');
    }
};