<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id(); // ✅ Primary key (bigIncrements)

            // Core messaging
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Message author');
                  
            $table->foreignId('receiver_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Message recipient');

            // Threading (for replies)
            $table->unsignedBigInteger('parent_id')->nullable()
                  ->comment('For message threads');
            
            // Ensuring `parent_id` references `id` properly
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('messages')
                  ->nullOnDelete();
            
            // Content
            $table->text('content');
            $table->string('subject')->nullable();
            $table->json('attachments')->nullable()
                  ->comment('File paths or metadata');

            // Metadata
            $table->timestamp('read_at')->nullable()
                  ->comment('When receiver viewed the message');
            $table->boolean('is_important')->default(false)
                  ->comment('Starred/flagged message');
            $table->enum('message_type', ['normal', 'system', 'notification', 'alert'])
                  ->default('normal');

            $table->boolean('is_draft')->default(false);

            // System fields
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['sender_id', 'receiver_id']);
            $table->index('created_at');
        });

        // ✅ Ensure MySQL supports full-text indexing before running this
        DB::statement('ALTER TABLE messages ADD FULLTEXT INDEX message_content_index (content)');
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
