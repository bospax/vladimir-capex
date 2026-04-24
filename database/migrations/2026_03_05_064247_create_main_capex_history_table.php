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
        Schema::create('main_capex_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('main_capex_id')->constrained('main_capex')->onDelete('cascade');
            
			$table->string('capex_number')->nullable();
			$table->integer('one_charging_id');
            $table->string('expenditure_type');
            $table->string('budget_type');
            $table->string('project_description');
            $table->decimal('enrolled_budget_amount', 15, 2)->default(0);
            $table->decimal('total_applied_amount', 15, 2)->default(0);
            $table->decimal('total_difference_amount', 15, 2)->default(0);
            $table->decimal('total_variance_amount', 15, 2)->default(0);
            $table->integer('requestor_id');
			$table->string('form_type');
			$table->integer('first_phase_level')->default(0);
			$table->integer('estimation_level')->default(0);
			$table->integer('estimation_approving_level')->default(0);
			$table->integer('major_level')->default(0);
			$table->string('status');
			$table->string('phase')->nullable();
			$table->integer('revision_no')->nullable()->default(1);

            $table->text('remarks')->nullable();
			$table->integer('approver_id')->nullable();
			$table->text('change_log')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_capex_history');
    }
};
