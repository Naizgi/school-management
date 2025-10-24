<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_activity_types_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id('activity_type_id');
            $table->string('activity_type', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('default_weight', 5, 2)->default(0);
            $table->string('grade_level', 50)->nullable();
            $table->string('semester_pattern', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['grade_level', 'semester_pattern']);
            $table->index(['is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('activity_types');
    }
};