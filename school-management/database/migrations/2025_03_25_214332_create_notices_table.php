<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id('notice_id');
            
            // Core fields from model
            $table->enum('notice_type', ['general', 'specific'])
                  ->default('general')
                  ->comment('General:for all, Specific:for individual student');
                  
            $table->foreignId('student_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->comment('Target student for specific notices');
            
            $table->string('title');
            $table->text('description');
            
            // Enhanced fields
            $table->foreignId('created_by')
                  ->constrained('users')
                  ->comment('Notice author');
                  
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])
                  ->default('medium');
                  
            $table->timestamp('published_at')->nullable()
                  ->comment('When notice should become visible');
            $table->timestamp('expires_at')->nullable()
                  ->comment('When notice should auto-archive');
            
            $table->boolean('is_published')->default(false);
            $table->boolean('requires_acknowledgment')->default(false);
            
            // System
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('notice_type');
            $table->index('student_id');
            $table->index('published_at');
            $table->index('is_published');
            $table->index(['published_at', 'expires_at']);
        });

        // For MySQL 8.0+ (improves notice searches)
        DB::statement(
            'CREATE FULLTEXT INDEX notice_search_index ON notices (title, description)'
        );
    }

    public function down()
    {
        Schema::dropIfExists('notices');
    }
};