<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedSmallInteger('shipment_batch')->default(1)->after('order_id');
            $table->string('invoice_number')->nullable()->after('shipment_batch');
            $table->foreignUuid('shipment_confirmation_id')->nullable()->after('invoice_number')->constrained('shipment_confirmations')->nullOnDelete();
            $table->decimal('quantity_billed', 15, 2)->nullable()->after('amount');
            $table->text('notes')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('transaction_time');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['shipment_confirmation_id']);
            $table->dropColumn([
                'shipment_batch',
                'invoice_number',
                'shipment_confirmation_id',
                'quantity_billed',
                'notes',
                'paid_at',
            ]);
        });
    }
};
