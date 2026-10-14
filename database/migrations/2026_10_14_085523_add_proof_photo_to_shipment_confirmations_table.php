<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom foto bukti penerimaan dan deskripsi konfirmasi
     * ke tabel shipment_confirmations.
     * Foto ini diunggah oleh UMKM saat mengkonfirmasi barang telah diterima.
     */
    public function up(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            // URL/path foto bukti barang yang diterima UMKM
            $table->string('proof_photo_url')->nullable()->after('tracking_notes');
            // Deskripsi/catatan dari UMKM saat konfirmasi penerimaan
            $table->text('confirmation_notes')->nullable()->after('proof_photo_url');
            // ID UMKM yang melakukan konfirmasi
            $table->foreignUuid('confirmed_by_umkm_id')
                ->nullable()
                ->constrained('umkm_profiles')
                ->onDelete('set null')
                ->after('confirmation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_confirmations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by_umkm_id');
            $table->dropColumn(['proof_photo_url', 'confirmation_notes']);
        });
    }
};
