<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id(); // Same as bigIncrements but more Laravel-idiomatic
            $table->string('class_name');
            $table->string('section')->nullable(); // Added for class subdivisions
            $table->string('academic_year'); // e.g. "2023-2024"
            $table->text('description')->nullable();
            
            // Relationships
            $table->foreignId('homeroom_teacher_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Main instructor for the class');
                  
            $table->foreignId('secondary_teacher_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Assistant instructor');
            
            // Metadata
            $table->integer('max_students')->default(30);
            $table->boolean('is_active')->default(true);
            $table->softDeletes(); // For archiving classes
            $table->timestamps();
            
            // Indexes
            $table->index('class_name');
            $table->index('academic_year');
            $table->unique(['class_name', 'section', 'academic_year']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('classes');
    }
};