<?php
// database/migrations/xxxx_xx_xx_xxxxxx_fix_activity_type_column_type.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Step 1: First, get all the current ENUM values from results table
        $enumValues = DB::select("
            SELECT COLUMN_TYPE 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'results' 
            AND COLUMN_NAME = 'activity_type'
        ")[0]->COLUMN_TYPE;

        // Extract enum values from something like: enum('assignment','quiz','test','exam','project')
        preg_match_all("/'([^']+)'/", $enumValues, $matches);
        $currentEnumValues = $matches[1] ?? [];

        echo "Current ENUM values in results table: " . implode(', ', $currentEnumValues) . "\n";

        // Step 2: Ensure these values exist in activity_types table
        foreach ($currentEnumValues as $value) {
            if (!DB::table('activity_types')->where('activity_type', $value)->exists()) {
                DB::table('activity_types')->insert([
                    'activity_type' => $value,
                    'description' => 'Migrated from existing ENUM values',
                    'default_weight' => 10.00,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "✓ Added '{$value}' to activity_types table\n";
            }
        }

        // Step 3: Convert the ENUM column to VARCHAR to match activity_types table
        Schema::table('results', function (Blueprint $table) {
            $table->string('activity_type', 100)->change();
        });

        echo "✓ Converted activity_type from ENUM to VARCHAR\n";

        // Step 4: Now add the foreign key constraint
        Schema::table('results', function (Blueprint $table) {
            $table->foreign('activity_type')
                  ->references('activity_type')
                  ->on('activity_types')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });

        echo "✅ Foreign key constraint added successfully!\n";
    }

    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropForeign(['activity_type']);
        });

        // Convert back to ENUM (you might want to keep it as VARCHAR though)
        // Schema::table('results', function (Blueprint $table) {
        //     $table->enum('activity_type', ['assignment', 'quiz', 'test', 'exam', 'project'])->change();
        // });
    }
};