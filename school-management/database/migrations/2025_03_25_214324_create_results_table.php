<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id('result_id');  // Matches your model's primaryKey

            // Core relationships
            $table->foreignId('student_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Student record');
                  
            $table->foreignId('course_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Course record');

            // Academic information
            $table->string('semester', 20)
                  ->comment('e.g. "Fall 2023" or "Term 1"');
            $table->enum('activity_type', [
                'assignment', 
                'quiz', 
                'test', 
                'exam', 
                'project',
                'participation'
            ])->default('quiz');
            
            $table->string('title');
            $table->date('assessment_date');
            
            // Scores and grading
            $table->float('score');
            $table->float('max_score')->default(100.0)
                  ->comment('Normalization reference');
            $table->float('weight')->default(1.0)
                  ->comment('For final grade calculation');
            $table->float('percentage')->virtualAs('(score/max_score)*weight')
                  ->comment('Auto-calculated percentage');
            
            // Financial (if applicable)
            $table->float('amount')->default(0.0)
                  ->comment('Associated fees if any');
            
            // Metadata
            $table->text('comments')->nullable();
            $table->json('rubric_data')->nullable()
                  ->comment('Detailed grading criteria');
            
            // Tracking
            $table->timestamp('published_at')->nullable()
                  ->comment('When results were released');
            $table->foreignId('graded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Instructor who graded');
            
            $table->timestamps();
            $table->softDeletes();  // For grade correction scenarios

            // Composite index for performance
            $table->index(['student_id', 'course_id', 'semester']);
            $table->index(['activity_type', 'assessment_date']);
        });

        // For MySQL full-text search
        DB::statement('ALTER TABLE results ADD FULLTEXT (title, comments)');
    }

    public function down()
    {
        Schema::dropIfExists('results');
    }
};