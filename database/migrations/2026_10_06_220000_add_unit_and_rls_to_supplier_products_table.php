<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_products', 'unit')) {
                $table->string('unit', 50)->nullable()->after('price');
            }
            if (! Schema::hasColumn('supplier_products', 'weekly_capacity')) {
                $table->decimal('weekly_capacity', 15, 2)->nullable()->after('stock_capacity');
            }
        });

        // Add RLS (Row Level Security) Policy for Supabase PostgreSQL
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE supplier_products ENABLE ROW LEVEL SECURITY;');
            DB::statement('DROP POLICY IF EXISTS supplier_products_owner_policy ON supplier_products;');
            DB::statement('
                CREATE POLICY supplier_products_owner_policy ON supplier_products
                FOR ALL
                USING (supplier_id IN (SELECT id FROM supplier_profiles WHERE user_id = auth.uid()));
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_products', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_products', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('supplier_products', 'weekly_capacity')) {
                $table->dropColumn('weekly_capacity');
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP POLICY IF EXISTS supplier_products_owner_policy ON supplier_products;');
        }
    }
};
