<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('partnership_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partnership_id')->constrained('partnerships')->onDelete('cascade');
            $table->string('evaluation_period');
            $table->decimal('target_volume', 12, 2);
            $table->decimal('realized_volume', 12, 2);
            $table->decimal('fulfillment_rate_percentage', 5, 2);
            $table->text('discrepancy_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('partnership_evaluations');
    }
};