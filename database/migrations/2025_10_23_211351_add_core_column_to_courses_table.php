<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Add core column after credit_hours
            $table->boolean('core')->default(false)->after('credit_hours');
            
            // Add index for better performance when filtering by core
            $table->index(['core', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex(['core', 'is_active']);
            
            // Then drop the column
            $table->dropColumn('core');
        });
    }
};