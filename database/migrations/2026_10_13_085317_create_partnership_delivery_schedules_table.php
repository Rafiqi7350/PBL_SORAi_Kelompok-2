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
        Schema::create('partnership_delivery_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partnership_id')->constrained('partnerships')->onDelete('cascade');
            $table->foreignUuid('supplier_product_id')->nullable()->constrained('supplier_products')->nullOnDelete();

            // Volume details
            $table->decimal('volume_per_delivery', 12, 2);
            $table->decimal('total_contract_volume', 12, 2)->nullable();
            $table->string('unit', 20)->default('kg');

            // Frequency and logistics schedule
            $table->string('frequency_type');
            $table->json('delivery_days')->nullable();
            $table->time('delivery_time')->default('05:30:00');

            // Auto-order automation status & dates
            $table->boolean('is_auto_order_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('special_instructions')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partnership_delivery_schedules');
    }
};
