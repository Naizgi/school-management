<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Same as bigIncrements but more Laravel-idiomatic
            
            // Authentication fields
            $table->string('user_name')->unique()->nullable();
            $table->string('phone_number')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->rememberToken();
            
            // Personal details
            $table->string('first_name');
            $table->string('last_name');
            $table->string('profile_picture')->default('default.png');
            
            // Role management
            $table->enum('role', ['parent', 'instructor', 'admin'])
                  ->default('parent');
            
            // Account status
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active');
            
            // Timestamps
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('role');
            $table->index('status');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};