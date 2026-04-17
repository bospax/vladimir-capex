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
        Schema::create('sub_capex', function (Blueprint $table) {
            $table->id();
			$table->foreignId('main_capex_id')->constrained('main_capex')->onDelete('cascade');
            $table->string('index');
            $table->string('type_of_subcapex');
            $table->string('remarks')->nullable();
            $table->string('building_number')->nullable();
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('estimate_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_capex');
    }
};
