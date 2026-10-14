<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            if (! Schema::hasColumn('partnerships', 'commodity_id')) {
                $table->foreignUuid('commodity_id')->nullable()->after('supplier_id')
                    ->constrained('commodities')->nullOnDelete();
            }
            if (! Schema::hasColumn('partnerships', 'duration')) {
                $table->string('duration', 100)->nullable()->after('delivery_schedule_notes');
            }
            if (! Schema::hasColumn('partnerships', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('partnerships', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            if (Schema::hasColumn('partnerships', 'commodity_id')) {
                $table->dropConstrainedForeignId('commodity_id');
            }
            $columns = array_filter(
                ['duration', 'rejection_reason', 'responded_at'],
                fn ($column) => Schema::hasColumn('partnerships', $column)
            );
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};