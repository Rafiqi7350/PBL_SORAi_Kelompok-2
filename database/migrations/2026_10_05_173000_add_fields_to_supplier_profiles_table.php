<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->foreignUuid('commodity_id')->nullable()->after('user_id')->constrained('commodities')->nullOnDelete();
            $table->string('max_capacity')->nullable()->after('warehouse_address');
            $table->string('delivery_area')->nullable()->after('max_capacity');
            $table->string('nib_number')->nullable()->after('delivery_area');
        });
    }

    public function down(): void {
        Schema::table('supplier_profiles', function (Blueprint $table) {
            $table->dropForeign(['commodity_id']);
            $table->dropColumn(['commodity_id', 'max_capacity', 'delivery_area', 'nib_number']);
        });
    }
};
