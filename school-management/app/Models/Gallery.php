<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->id(); // ✅ Primary key using bigIncrements
            
            // Core fields
            $table->foreignId('event_id')
                  ->nullable() // ✅ Allows null for onDelete(null)
                  ->constrained('events')
                  ->nullOnDelete()
                  ->comment('Associated event');
                  
            $table->string('image_url'); // ✅ Stores the image path/URL
            $table->string('caption')->nullable(); // ✅ Optional caption
            $table->boolean('is_featured')->default(false); // ✅ Marks featured images
            
            // Storage path for local images
            $table->string('storage_path')
                  ->nullable()
                  ->comment('Local storage path if not using full URL');
            
            // User who uploaded the image
            $table->foreignId('uploaded_by')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('User who uploaded the image');

            // System fields
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for fast queries
            $table->index('event_id');  // ✅ For filtering images by event
            $table->index('is_featured'); // ✅ For fetching featured images
        });

        // MySQL 8.0+ Index for caption searches
        DB::statement(
            'CREATE INDEX gallery_caption_index ON galleries (caption(100))'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('galleries');
    }
};
