<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('parents', function (Blueprint $table) {
            $table->id('parent_id'); // Custom primary key name maintained
            
            // Relationships
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete()
                  ->comment('Associated user account');
            
            // Parent-specific fields
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->enum('preferred_contact', ['email', 'phone', 'sms'])
                  ->default('phone')
                  ->comment('Preferred communication method');
            
            // Emergency contacts (redundant storage for quick access)
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Verification
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('is_verified');
        });
    }

    public function down()
    {
        Schema::dropIfExists('parents');
    }
};