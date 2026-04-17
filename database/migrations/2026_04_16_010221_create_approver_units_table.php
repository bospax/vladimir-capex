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
        Schema::create('approver_units', function (Blueprint $table) {
            $table->id();
			$table->integer('unit_id');
			$table->integer('approver_id');
			$table->integer('subunit_id')->nullable();
			$table->integer('one_charging_id');
			$table->integer('level');
			$table->string('approver_set_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approver_units');
    }
};
