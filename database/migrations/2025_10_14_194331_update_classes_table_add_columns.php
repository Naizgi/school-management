<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('classes', function (Blueprint $table) {
            // Drop unique index safely
            $table->dropUnique(['class_name', 'section', 'academic_year']);

            // Drop 'section' column if exists
            if (Schema::hasColumn('classes', 'section')) {
                $table->dropColumn('section');
            }

            // Add new columns
            if (!Schema::hasColumn('classes', 'grade')) {
                $table->string('grade')->nullable()->after('section_name');
            }

            if (!Schema::hasColumn('classes', 'current_students')) {
                $table->integer('current_students')->default(0)->after('grade');
            }

            if (!Schema::hasColumn('classes', 'room_number')) {
                $table->string('room_number')->nullable()->after('current_students');
            }
        });
    }

    public function down()
    {
        Schema::table('classes', function (Blueprint $table) {
            // Drop newly added columns
            if (Schema::hasColumn('classes', 'grade')) {
                $table->dropColumn('grade');
            }

            if (Schema::hasColumn('classes', 'current_students')) {
                $table->dropColumn('current_students');
            }

            if (Schema::hasColumn('classes', 'room_number')) {
                $table->dropColumn('room_number');
            }

            // Restore 'section' column
            if (!Schema::hasColumn('classes', 'section')) {
                $table->string('section')->nullable()->after('section_name');
            }

            // Recreate the unique index
            $table->unique(['class_name', 'section', 'academic_year']);
        });
    }
};
