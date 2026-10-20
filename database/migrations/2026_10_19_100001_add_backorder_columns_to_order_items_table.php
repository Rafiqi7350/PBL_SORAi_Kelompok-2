<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('quantity_delivered', 10, 2)->default(0)->after('quantity');

            $table->decimal('quantity_remaining', 10, 2)->default(0)->after('quantity_delivered');

            $table->string('fulfillment_item_status', 50)->default('pending')->after('subtotal');
        });

        DB::table('order_items')->where('quantity_remaining', 0)->update([
            'quantity_remaining' => DB::raw('quantity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'quantity_delivered',
                'quantity_remaining',
                'fulfillment_item_status',
            ]);
        });
    }
};
