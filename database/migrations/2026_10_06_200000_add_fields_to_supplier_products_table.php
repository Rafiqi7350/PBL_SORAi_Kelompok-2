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
        Schema::table('supplier_products', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_products', 'description')) {
                $table->text('description')->nullable()->after('stock_status');
            }
            if (! Schema::hasColumn('supplier_products', 'minimum_order')) {
                $table->string('minimum_order')->default('1')->after('description');
            }
            if (! Schema::hasColumn('supplier_products', 'is_halal_certified')) {
                $table->boolean('is_halal_certified')->default(false)->after('minimum_order');
            }
            if (! Schema::hasColumn('supplier_products', 'quality_grade')) {
                $table->string('quality_grade', 100)->nullable()->after('is_halal_certified');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('supplier_products', 'description')) {
                $columnsToDrop[] = 'description';
            }
            if (Schema::hasColumn('supplier_products', 'minimum_order')) {
                $columnsToDrop[] = 'minimum_order';
            }
            if (Schema::hasColumn('supplier_products', 'is_halal_certified')) {
                $columnsToDrop[] = 'is_halal_certified';
            }
            if (Schema::hasColumn('supplier_products', 'quality_grade')) {
                $columnsToDrop[] = 'quality_grade';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
