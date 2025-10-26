<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id(); // Primary key (same as bigIncrements but more modern)
            
            // Relationships
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Link to user account');
                  
            $table->foreignId('class_id')
                  ->nullable()
                  ->constrained('classes')
                  ->nullOnDelete()
                  ->comment('Primary assigned class');
            
            // Instructor details (redundant from users table for performance)
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('specialization')->nullable();
            
            // Status flags
            $table->boolean('is_active')->default(true);
            $table->date('hire_date')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('class_id');
            $table->index('is_active');
        });

        // Set storage engine (not usually needed as Laravel defaults to InnoDB)
        DB::statement('ALTER TABLE instructors ENGINE = InnoDB');
    }

    public function down()
    {
        Schema::dropIfExists('instructors');
    }
};