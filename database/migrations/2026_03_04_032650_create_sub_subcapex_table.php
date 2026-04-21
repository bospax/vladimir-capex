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
        Schema::create('sub_subcapex', function (Blueprint $table) {
            $table->id();
			$table->foreignId('sub_capex_id')->constrained('sub_capex')->onDelete('cascade');
            $table->string('particulars');
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->string('attachments')->nullable();
            $table->integer('estimator_id');
			$table->integer('estimation_level')->default(1);
			$table->string('estimation_status');
			$table->integer('estimation_approver_id')->nullable();
            $table->string('estimation_approving_status')->nullable();
			$table->integer('is_applicable')->default(1);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_subcapex');
    }
};
