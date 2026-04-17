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
        Schema::create('additional_cost', function (Blueprint $table) {
            $table->id();
			$table->string('capex_number');
            $table->integer('sub_capex_id');
            $table->string('sub_subcapex_id')->nullable();
            $table->integer('amount');
            $table->string('attachment')->nullable();
			$table->integer('requestor_id');
			$table->integer('level');
			$table->string('status');
			$table->string('phase')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_cost');
    }
};
