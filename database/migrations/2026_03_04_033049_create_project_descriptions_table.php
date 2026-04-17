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
        Schema::create('project_descriptions', function (Blueprint $table) {
            $table->id();
			$table->string('id_number')->nullable();
			$table->string('description');
			$table->string('type_of_expenditure')->nullable();
			$table->decimal('years', 15, 2)->nullable();
			$table->decimal('quantity', 15, 2)->nullable();
			$table->decimal('unit_cost', 15, 2)->nullable();
			$table->decimal('enrolled_budget_amount', 15, 2)->default(0);
			$table->string('remarks')->nullable();
			$table->date('date_applied')->nullable();
			$table->string('cost_applied')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_descriptions');
    }
};
