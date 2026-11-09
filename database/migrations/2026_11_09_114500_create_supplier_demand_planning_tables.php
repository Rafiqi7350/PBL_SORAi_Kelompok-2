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
        // 1. Status Inventori Fisik & Ambang Batas Pemasok
        Schema::create('supplier_inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->foreignUuid('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->decimal('current_stock', 12, 2)->default(0.00);
            $table->decimal('reserved_stock', 12, 2)->default(0.00);
            $table->decimal('safety_stock', 12, 2)->default(0.00);
            $table->decimal('reorder_point', 12, 2)->default(0.00);
            $table->string('unit', 20)->default('kg');
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'commodity_id'], 'uq_supp_inv_commodity');
            $table->index(['supplier_id', 'reorder_point']);
        });

        // 2. Daftar Produsen / Petani Hulu (Pihak Pertama tempat Pemasok Kulakan)
        Schema::create('supplier_material_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->foreignUuid('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->string('producer_name');
            $table->string('contact_phone', 50)->nullable();
            $table->integer('lead_time_days')->default(2);
            $table->decimal('minimum_order_qty', 12, 2)->default(50.00);
            $table->decimal('unit_cost_price', 12, 2)->default(0.00);
            $table->boolean('is_primary_source')->default(true);
            $table->timestamps();

            $table->index(['supplier_id', 'commodity_id']);
        });

        // 3. Batch Eksekusi Peramalan Permintaan
        Schema::create('demand_forecast_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->string('forecast_horizon', 20)->default('7_DAYS'); // '7_DAYS', '30_DAYS', '90_DAYS'
            $table->string('model_used', 50)->default('COLLABORATIVE_HYBRID');
            $table->decimal('model_accuracy_mape', 5, 2)->nullable();
            $table->json('parameters')->nullable();
            $table->timestamp('run_at')->useCurrent();
            $table->timestamps();

            $table->index(['supplier_id', 'run_at']);
        });

        // 4. Detail Hasil Peramalan per Komoditas & Tanggal
        Schema::create('demand_forecast_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('forecast_run_id')->constrained('demand_forecast_runs')->cascadeOnDelete();
            $table->foreignUuid('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->date('forecast_date');
            $table->decimal('baseline_demand', 12, 2)->default(0.00);
            $table->decimal('upper_bound_95', 12, 2)->default(0.00);
            $table->decimal('lower_bound_95', 12, 2)->default(0.00);
            $table->decimal('contract_demand', 12, 2)->default(0.00);
            $table->decimal('collaborative_demand', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index(['forecast_run_id', 'commodity_id', 'forecast_date'], 'idx_forecast_item_lookup');
        });

        // 5. Rekomendasi Pemesanan Pengadaan (Purchase Suggestions / Draft PO)
        Schema::create('supplier_purchase_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->foreignUuid('commodity_id')->constrained('commodities')->cascadeOnDelete();
            $table->foreignUuid('material_source_id')->nullable()->constrained('supplier_material_sources')->nullOnDelete();
            $table->date('suggested_order_date');
            $table->date('target_arrival_date');
            $table->decimal('suggested_quantity', 12, 2);
            $table->decimal('estimated_cost', 14, 2)->default(0.00);
            $table->string('urgency_level', 20)->default('NORMAL'); // 'CRITICAL', 'WARNING', 'NORMAL'
            $table->string('status', 30)->default('PENDING_REVIEW'); // 'PENDING_REVIEW', 'ORDERED', 'IGNORED'
            $table->string('po_reference_number', 100)->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status', 'urgency_level'], 'idx_purch_sugg_status');
        });

        // 6. Riwayat Skenario What-If Simulator
        Schema::create('what_if_simulations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->cascadeOnDelete();
            $table->string('scenario_name');
            $table->decimal('demand_shock_pct', 5, 2)->default(0.00);
            $table->integer('lead_time_delay_days')->default(0);
            $table->json('results_summary')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('what_if_simulations');
        Schema::dropIfExists('supplier_purchase_suggestions');
        Schema::dropIfExists('demand_forecast_items');
        Schema::dropIfExists('demand_forecast_runs');
        Schema::dropIfExists('supplier_material_sources');
        Schema::dropIfExists('supplier_inventory_items');
    }
};
