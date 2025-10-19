<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('attendance_alerts', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('student_id');
        $table->string('alert_type')->default('absence_10_days');
        $table->string('message');
        $table->boolean('is_confirmed')->default(false);
        $table->unsignedBigInteger('confirmed_by')->nullable(); // teacher/admin user_id
        $table->timestamps();

        $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_alerts');
    }
};
