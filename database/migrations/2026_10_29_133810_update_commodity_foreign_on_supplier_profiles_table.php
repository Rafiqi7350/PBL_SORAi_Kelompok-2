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
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropForeign(['commodity_id']);
        });

        // Set existing non-matching values to null to avoid constraint errors
        DB::table('supplier_profiles')->update(['commodity_id' => null]);

        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->foreign('commodity_id')
                ->references('id')
                ->on('commodity_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropForeign(['commodity_id']);
        });

        DB::table('supplier_profiles')->update(['commodity_id' => null]);

        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->foreign('commodity_id')
                ->references('id')
                ->on('commodities')
                ->nullOnDelete();
        });
    }
};
