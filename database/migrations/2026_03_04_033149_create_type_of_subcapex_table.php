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
        Schema::create('type_of_subcapex', function (Blueprint $table) {
            $table->id();
			$table->foreignId('type_of_expenditure_id')->constrained('type_of_expenditures')->onDelete('cascade');
            $table->string('name');
            $table->integer('with_remarks')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_of_subcapex');
    }
};
