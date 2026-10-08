<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partnership_id')->constrained('partnerships')->onDelete('cascade');
            $table->string('period_month_year');
            $table->decimal('estimated_quantity', 12, 2);
            $table->string('supplier_confirmation_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_plans');
    }
};
