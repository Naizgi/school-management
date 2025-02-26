<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('class_id');
            $table->date('dob');
            $table->string('gender');
            $table->timestamps();

            // Ensure that foreign key constraints are correctly formed
            $table->foreign('parent_id')
                ->references('id')->on('parents')
                ->onDelete('cascade')
                ->onUpdate('cascade');  // Add 'onUpdate' for consistency
            $table->foreign('class_id')
                ->references('id')->on('classes')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
