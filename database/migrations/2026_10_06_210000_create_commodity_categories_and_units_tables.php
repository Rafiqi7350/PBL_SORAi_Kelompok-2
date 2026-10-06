<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Commodity Categories Table
        if (!Schema::hasTable('commodity_categories')) {
            Schema::create('commodity_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 2. Commodity Units Table
        if (!Schema::hasTable('commodity_units')) {
            Schema::create('commodity_units', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name')->unique(); // kg, liter, pcs, dus, gram, etc.
                $table->string('symbol', 20)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // 3. Add description to commodities table if not exists
        if (Schema::hasTable('commodities')) {
            Schema::table('commodities', function (Blueprint $table) {
                if (!Schema::hasColumn('commodities', 'description')) {
                    $table->text('description')->nullable()->after('government_ref_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commodity_categories');
        Schema::dropIfExists('commodity_units');
        if (Schema::hasTable('commodities') && Schema::hasColumn('commodities', 'description')) {
            Schema::table('commodities', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
