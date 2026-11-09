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
        Schema::create('supplier_performance_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->foreignUuid('partnership_id')->nullable()->constrained('partnerships')->nullOnDelete();
            $table->decimal('final_score', 5, 2)->default(0.00);
            $table->decimal('star_rating', 3, 1)->default(0.0);
            $table->string('grade', 10)->default('UNRATED');
            $table->decimal('on_time_delivery_score', 5, 2)->default(0.00);
            $table->decimal('quota_fulfillment_score', 5, 2)->default(0.00);
            $table->decimal('quality_rating_score', 5, 2)->default(0.00);
            $table->json('metrics_breakdown')->nullable();
            $table->json('recommendations')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['supplier_id', 'calculated_at']);
            $table->index(['partnership_id', 'grade']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_scores');
    }
};
