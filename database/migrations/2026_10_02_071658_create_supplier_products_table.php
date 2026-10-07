<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('supplier_profiles')->onDelete('cascade');
            $table->foreignUuid('commodity_id')->constrained('commodities')->onDelete('cascade');
            $table->decimal('price', 15, 2);
            $table->string('stock_capacity')->nullable();
            $table->string('stock_status', 50)->default('Tersedia');
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
