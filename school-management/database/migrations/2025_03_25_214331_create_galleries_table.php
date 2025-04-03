<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id('image_id');  // Matches your $primaryKey
            
            // Core fields from model
            $table->foreignId('event_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete()
                  ->comment('Associated event');
                  
            $table->string('image_url');
            $table->string('caption')->nullable();
            $table->boolean('is_featured')->default(false);
            
            // Added fields to support model's URL transformations
            $table->string('storage_path')
                  ->nullable()
                  ->comment('Local storage path if not using full URL');
            
            // System fields
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->comment('User who uploaded the image');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for model scopes
            $table->index('event_id');  // For forEvent() scope
            $table->index('is_featured');  // For featured() scope
        });

        // For MySQL 8.0+ (improves caption searches)
        DB::statement(
            'CREATE INDEX gallery_caption_index ON galleries (caption(100))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};