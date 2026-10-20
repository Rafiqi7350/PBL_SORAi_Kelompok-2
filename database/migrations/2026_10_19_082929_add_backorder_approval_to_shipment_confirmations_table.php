<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            $table->date('backorder_estimated_delivery_date')->nullable()->after('shipped_quantity');

            $table->text('backorder_notes')->nullable()->after('backorder_estimated_delivery_date');

            $table->string('backorder_approval_status', 30)->default('none')->after('backorder_notes');

            $table->text('backorder_rejection_reason')->nullable()->after('backorder_approval_status');

            $table->timestamp('backorder_approved_at')->nullable()->after('backorder_rejection_reason');
            $table->timestamp('backorder_rejected_at')->nullable()->after('backorder_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            $table->dropColumn([
                'backorder_estimated_delivery_date',
                'backorder_notes',
                'backorder_approval_status',
                'backorder_rejection_reason',
                'backorder_approved_at',
                'backorder_rejected_at',
            ]);
        });
    }
};
