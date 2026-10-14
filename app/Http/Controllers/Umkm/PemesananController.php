<?php

namespace App\Http\Controllers\Umkm;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShipmentConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PemesananController extends Controller
{
    /**
     * Tampilkan daftar pesanan UMKM yang sedang login.
     */
    public function index()
    {
        $umkm = Auth::user()?->umkmProfile;

        $orders = Order::with([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'shipmentConfirmation',
        ])
            ->whereHas('partnership', function ($q) use ($umkm) {
                $q->where('umkm_id', $umkm?->id);
            })
            ->latest()
            ->get();

        return view('umkm.pemesanan.index', compact('orders'));
    }

    /**
     * Tampilkan detail modal konfirmasi penerimaan pesanan.
     * Digunakan oleh AJAX untuk mengambil data order & foto bukti.
     */
    public function showKonfirmasi(Order $order)
    {
        $order->load([
            'partnership.supplier',
            'items.supplierProduct.commodity',
            'shipmentConfirmation',
        ]);

        return response()->json([
            'order' => $order,
            'shipment' => $order->shipmentConfirmation,
        ]);
    }

    /**
     * UMKM mengkonfirmasi penerimaan barang beserta upload foto bukti.
     */
    public function konfirmasi(Request $request, Order $order)
    {
        $request->validate([
            'proof_photo' => 'required|image|max:5120', // max 5MB
            'confirmation_notes' => 'nullable|string|max:500',
        ]);

        $umkm = Auth::user()?->umkmProfile;

        // Upload foto ke storage
        $photoPath = $request->file('proof_photo')->store('shipment-proofs', 'public');

        // Simpan atau update shipment_confirmation
        ShipmentConfirmation::updateOrCreate(
            ['order_id' => $order->id],
            [
                'received_at' => now(),
                'fulfillment_status' => 'BARANG_DITERIMA',
                'proof_photo_url' => Storage::url($photoPath),
                'confirmation_notes' => $request->input('confirmation_notes'),
                'confirmed_by_umkm_id' => $umkm?->id,
            ]
        );

        // Update status order
        $order->update(['order_status' => 'BARANG_DITERIMA']);

        return redirect()->back()->with('success', '✓ Penerimaan barang berhasil dikonfirmasi!');
    }
}
