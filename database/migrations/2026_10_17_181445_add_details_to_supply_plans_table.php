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
        Schema::table('supply_plans', function (Blueprint $table) {
            $table->string('commodity_name')->nullable()->after('partnership_id');
            $table->string('unit')->default('kg')->after('estimated_quantity');
            $table->decimal('confirmed_quantity', 12, 2)->nullable()->after('supplier_confirmation_status');
            $table->text('supplier_notes')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_plans', function (Blueprint $table) {
            $table->dropColumn(['commodity_name', 'unit', 'confirmed_quantity', 'supplier_notes']);
        });
    }
};
