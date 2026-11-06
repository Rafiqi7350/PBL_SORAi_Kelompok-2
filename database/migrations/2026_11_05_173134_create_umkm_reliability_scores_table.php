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
        Schema::create('umkm_reliability_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('umkm_id')->constrained('umkm_profiles')->cascadeOnDelete();
            $table->foreignUuid('partnership_id')->nullable()->constrained('partnerships')->nullOnDelete();
            $table->decimal('final_score', 5, 2)->default(0.00);
            $table->string('grade', 10)->default('UNRATED');
            $table->decimal('payment_discipline_score', 5, 2)->default(0.00);
            $table->decimal('order_consistency_score', 5, 2)->default(0.00);
            $table->decimal('tenure_history_score', 5, 2)->default(0.00);
            $table->json('metrics_breakdown')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['umkm_id', 'calculated_at']);
            $table->index(['partnership_id', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umkm_reliability_scores');
    }
};
