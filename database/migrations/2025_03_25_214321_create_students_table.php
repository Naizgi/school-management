<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('parent_phone_number');
            $table->string('name');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('roll_number');
            $table->string('academic_year');
            $table->date('date_of_admission');
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->date('date_of_birth');
            $table->string('address');
            $table->string('profile_picture')->nullable();
            $table->timestamps();
        });

        // Add the foreign key constraint in a separate statement
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('parent_phone_number')
                  ->references('phone_number')
                  ->on('users')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('class_id')
                  ->references('id')
                  ->on('classes')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
};