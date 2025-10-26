<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id('id');  // Matches your $primaryKey
            
            // Core event details
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Event metadata
            $table->string('location')->nullable();
            $table->enum('event_type', [
                'academic',
                'sports',
                'cultural',
                'holiday',
                'meeting',
                'other'
            ])->default('academic');
            
            $table->boolean('is_public')->default(true);
            $table->boolean('requires_rsvp')->default(false);
            
            // Organization
            $table->foreignId('organizer_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Event organizer');
                  
            $table->foreignId('class_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->comment('Specific class if applicable');
            
            // Media and attachments
            $table->string('featured_image')->nullable();
            $table->json('attachments')->nullable()
                  ->comment('Additional files or links');
            
            // System
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('date');
            $table->index('event_type');
            $table->index('is_public');
            $table->index(['date', 'class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};