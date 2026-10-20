<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            $table->integer('shipment_batch')->default(1)->after('order_id');

            $table->string('backorder_action', 50)->nullable()->after('shipment_batch');

            $table->decimal('shipped_quantity', 10, 2)->default(0)->after('backorder_action');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            $table->dropColumn([
                'shipment_batch',
                'backorder_action',
                'shipped_quantity',
            ]);
        });
    }
};
