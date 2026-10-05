<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('owner_name');
            $table->string('phone');
            $table->text('warehouse_address');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('supplier_profiles');
    }
};