<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->unsignedSmallInteger('term_of_payment_days')->default(0)->after('status');
            $table->decimal('credit_limit', 15, 2)->default(0.00)->after('term_of_payment_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partnerships', function (Blueprint $table) {
            $table->dropColumn(['term_of_payment_days', 'credit_limit']);
        });
    }
};
