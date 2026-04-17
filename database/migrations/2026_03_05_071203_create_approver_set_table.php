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
        Schema::create('approver_set', function (Blueprint $table) {
            $table->id();
			$table->foreignId('main_capex_id')->constrained('main_capex')->onDelete('cascade'); // Link to main_capex
			$table->integer('user_id'); // approver's user ID
            $table->integer('level'); // 1, 2, 3, 4, 5, 6 etc.
			$table->string('approver_set_name'); // FIRST PHASE APPROVER 1, FIRST PHASE APPROVER 2, ESTIMATION APPROVER 1, ESTIMATION APPROVER 2, MAJOR APPROVER 1, MAJOR APPROVER 2 etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approver_set');
    }
};
