<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\ShipmentConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PengirimanController extends Controller
{
    public function index(Request $request)
    {
        $supplier = Auth::user()?->supplierProfile;
        $supplierPartnershipIds = $supplier ? Partnership::where('supplier_id', $supplier->id)->pluck('id') : collect();

        $tab = $request->input('tab', 'all');
        $search = $request->input('search');

        $query = Order::with([
            'partnership.umkm',
            'items.supplierProduct.commodity',
            'shipmentConfirmations',
            'payments',
        ])
            ->whereIn('partnership_id', $supplierPartnershipIds)
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('partnership.umkm', function ($uq) use ($search) {
                        $uq->where('restaurant_name', 'ilike', "%{$search}%")
                            ->orWhere('owner_name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('items.supplierProduct.commodity', function ($cq) use ($search) {
                        $cq->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        switch ($tab) {
            case 'pending':
                $query->whereIn('order_status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, 'pending', 'confirmed']);
                break;
            case 'shipping':
                $query->whereIn('order_status', [Order::STATUS_SHIPPED, Order::STATUS_PARTIALLY_SHIPPED, Order::STATUS_WAITING_BACKORDER_APPROVAL, 'DIKIRIM']);
                break;
            case 'backorder':
                $query->where(function ($q) {
                    $q->whereIn('order_status', [Order::STATUS_BACKORDER_PENDING, Order::STATUS_WAITING_BACKORDER_APPROVAL])
                        ->orWhereHas('items', function ($iq) {
                            $iq->where('quantity_remaining', '>', 0)
                                ->orWhere('fulfillment_item_status', 'backorder');
                        })
                        ->orWhereHas('shipmentConfirmations', function ($sq) {
                            $sq->where('backorder_action', 'create_backorder');
                        });
                });
                break;
            case 'completed':
                $query->whereIn('order_status', [Order::STATUS_COMPLETED, Order::STATUS_COMPLETED_PARTIAL, 'BARANG_DITERIMA']);
                break;
        }

        $orders = $query->paginate(15)->withQueryString();

        // 1 SINGLE KONSOLIDASI QUERY untuk menghitung seluruh tab status pengiriman
        $counts = DB::table('orders')
            ->whereIn('partnership_id', $supplierPartnershipIds)
            ->selectRaw("
                COUNT(*) as count_all,
                COUNT(CASE WHEN order_status IN ('".Order::STATUS_PENDING."', '".Order::STATUS_CONFIRMED."', 'pending', 'confirmed') THEN 1 END) as count_pending,
                COUNT(CASE WHEN order_status IN ('".Order::STATUS_SHIPPED."', '".Order::STATUS_PARTIALLY_SHIPPED."', '".Order::STATUS_WAITING_BACKORDER_APPROVAL."', 'DIKIRIM') THEN 1 END) as count_shipping,
                COUNT(CASE WHEN order_status IN ('".Order::STATUS_COMPLETED."', '".Order::STATUS_COMPLETED_PARTIAL."', 'BARANG_DITERIMA') THEN 1 END) as count_completed,
                COUNT(CASE WHEN order_status IN ('".Order::STATUS_BACKORDER_PENDING."', '".Order::STATUS_WAITING_BACKORDER_APPROVAL."') THEN 1 END) as count_backorder
            ")
            ->first();

        $countAll = (int) ($counts->count_all ?? 0);
        $countPending = (int) ($counts->count_pending ?? 0);
        $countShipping = (int) ($counts->count_shipping ?? 0);
        $countCompleted = (int) ($counts->count_completed ?? 0);
        $countBackorder = (int) ($counts->count_backorder ?? 0);

        return view('supplier.pengiriman.index', compact(
            'orders',
            'tab',
            'search',
            'countAll',
            'countPending',
            'countShipping',
            'countBackorder',
            'countCompleted'
        ));
    }

    public function prosesKirim(Request $request, Order $order)
    {
        $order->load(['items.supplierProduct', 'shipmentConfirmations']);

        $totalOrdered = $order->items->sum('quantity');

        $request->validate([
            'qty_to_ship' => ['required', 'numeric', 'min:0', 'max:'.$totalOrdered],
            'backorder_action' => ['nullable', 'string', 'in:none,create_backorder,no_backorder'],
            'tracking_notes' => ['nullable', 'string', 'max:500'],
            'backorder_estimated_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'backorder_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $qtyToShip = (float) $request->input('qty_to_ship');
        $backorderAction = $request->input('backorder_action', 'none');
        $notes = $request->input('tracking_notes');
        $backorderEta = $request->input('backorder_estimated_delivery_date');
        $backorderNotes = $request->input('backorder_notes');

        if ($backorderAction === 'create_backorder') {
            $request->validate([
                'backorder_estimated_delivery_date' => ['required', 'date', 'after_or_equal:today'],
                'backorder_notes' => ['required', 'string', 'max:500'],
            ]);
        }

        DB::transaction(function () use ($order, $totalOrdered, $qtyToShip, $backorderAction, $notes, $backorderEta, $backorderNotes) {
            $orderCode = strtoupper(substr($order->id, 0, 8));

            if ($qtyToShip >= $totalOrdered && $backorderAction !== 'no_backorder') {
                $shipment1 = ShipmentConfirmation::create([
                    'order_id' => $order->id,
                    'shipment_batch' => 1,
                    'shipped_quantity' => $qtyToShip,
                    'backorder_action' => 'none',
                    'fulfillment_status' => 'DIKIRIM',
                    'shipped_at' => now(),
                    'tracking_notes' => $notes ?: 'Pengiriman penuh langsung dari gudang pemasok.',
                ]);

                foreach ($order->items as $item) {
                    $item->update([
                        'quantity_delivered' => $item->quantity,
                        'quantity_remaining' => 0,
                        'fulfillment_item_status' => 'fulfilled',
                    ]);
                }

                $order->update([
                    'order_status' => Order::STATUS_SHIPPED,
                ]);

                Payment::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'shipment_batch' => 1,
                    ],
                    [
                        'shipment_confirmation_id' => $shipment1->id,
                        'invoice_number' => "INV/{$orderCode}/B1",
                        'quantity_billed' => $qtyToShip,
                        'amount' => (float) $order->total_amount,
                        'payment_status' => 'UNPAID',
                        'notes' => 'Tagihan Pengiriman Penuh (Batch 1)',
                    ]
                );

            } elseif ($backorderAction === 'create_backorder') {
                $shipment1 = ShipmentConfirmation::create([
                    'order_id' => $order->id,
                    'shipment_batch' => 1,
                    'shipped_quantity' => $qtyToShip,
                    'backorder_action' => 'create_backorder',
                    'backorder_estimated_delivery_date' => $backorderEta,
                    'backorder_notes' => $backorderNotes,
                    'backorder_approval_status' => 'pending',
                    'fulfillment_status' => 'MENUNGGU_PERSETUJUAN_UMKM',
                    'shipped_at' => null,
                    'tracking_notes' => $notes ?: 'Pengajuan Backorder (Kloter 1: '.number_format($qtyToShip, 0).', Sisa di-backorder). Menunggu persetujuan UMKM.',
                ]);

                $remainingToDistribute = $qtyToShip;
                foreach ($order->items as $item) {
                    $itemQty = (float) $item->quantity;
                    $itemDelivered = min($itemQty, $remainingToDistribute);
                    $itemRemaining = max(0, $itemQty - $itemDelivered);
                    $remainingToDistribute -= $itemDelivered;

                    $item->update([
                        'quantity_delivered' => $itemDelivered,
                        'quantity_remaining' => $itemRemaining,
                        'fulfillment_item_status' => 'backorder',
                    ]);
                }

                $order->update([
                    'order_status' => Order::STATUS_WAITING_BACKORDER_APPROVAL,
                ]);

            } else {
                if ($qtyToShip <= 0) {
                    $shipment1 = ShipmentConfirmation::create([
                        'order_id' => $order->id,
                        'shipment_batch' => 1,
                        'shipped_quantity' => 0,
                        'backorder_action' => 'no_backorder',
                        'fulfillment_status' => 'DIBATALKAN_STOK_KOSONG',
                        'shipped_at' => null,
                        'tracking_notes' => $notes ?: 'Pesanan dibatalkan karena stok di gudang tidak tersedia.',
                    ]);

                    foreach ($order->items as $item) {
                        $item->update([
                            'quantity_delivered' => 0,
                            'quantity_remaining' => 0,
                            'fulfillment_item_status' => 'cancelled',
                        ]);
                    }

                    $order->update([
                        'total_amount' => 0,
                        'order_status' => Order::STATUS_CANCELLED,
                    ]);
                } else {
                    $shipment1 = ShipmentConfirmation::create([
                        'order_id' => $order->id,
                        'shipment_batch' => 1,
                        'shipped_quantity' => $qtyToShip,
                        'backorder_action' => 'no_backorder',
                        'backorder_notes' => $notes ?: 'Pemasok mengajukan pemenuhan sebagian (hanya sanggup kirim '.number_format($qtyToShip, 0).', sisa ditiadakan karena stok habis).',
                        'backorder_approval_status' => 'pending',
                        'fulfillment_status' => 'MENUNGGU_PERSETUJUAN_UMKM',
                        'shipped_at' => null,
                        'tracking_notes' => $notes ?: 'Pengajuan No Backorder (Hanya dapat kirim '.number_format($qtyToShip, 0).', sisa ditiadakan). Menunggu persetujuan UMKM.',
                    ]);

                    $remainingToDistribute = $qtyToShip;
                    foreach ($order->items as $item) {
                        $itemQty = (float) $item->quantity;
                        $itemDelivered = min($itemQty, $remainingToDistribute);
                        $itemRemaining = max(0, $itemQty - $itemDelivered);
                        $remainingToDistribute -= $itemDelivered;

                        $item->update([
                            'quantity_delivered' => $itemDelivered,
                            'quantity_remaining' => $itemRemaining,
                            'fulfillment_item_status' => 'no_backorder_pending',
                        ]);
                    }

                    $order->update([
                        'order_status' => Order::STATUS_WAITING_BACKORDER_APPROVAL,
                    ]);

                }
            }
        });

        if ($qtyToShip >= $totalOrdered && $backorderAction !== 'no_backorder') {
            return redirect()->back()->with('success', '✓ Stok sesuai! Seluruh pesanan berhasil dikirim & Invoice Batch 1 diterbitkan.');
        } elseif ($backorderAction === 'create_backorder') {
            return redirect()->back()->with('success', '✓ Pengajuan Backorder berhasil dikirim ke pihak UMKM. Menunggu persetujuan UMKM sebelum barang diberangkatkan.');
        } elseif ($qtyToShip <= 0) {
            return redirect()->back()->with('success', '✓ Pesanan dibatalkan karena stok di gudang kosong.');
        } else {
            return redirect()->back()->with('success', '✓ Pengajuan Pemenuhan Sebagian (Tanpa Backorder) berhasil dikirim ke pihak UMKM. Menunggu persetujuan UMKM sebelum barang diberangkatkan.');
        }
    }

    public function kirimBatch1(Request $request, Order $order)
    {
        $order->load(['items.supplierProduct', 'shipmentConfirmations']);

        $batch1 = $order->shipmentConfirmations->firstWhere('shipment_batch', 1);

        if (! $batch1 || ! in_array($batch1->backorder_action, ['create_backorder', 'no_backorder'])) {
            return redirect()->back()->with('error', 'Pesanan ini tidak memiliki pengajuan pengiriman yang menunggu dikirim.');
        }

        if ($batch1->backorder_approval_status !== 'approved') {
            return redirect()->back()->with('error', 'Pengajuan belum disetujui oleh pihak UMKM.');
        }

        if ($batch1->shipped_at !== null) {
            return redirect()->back()->with('error', 'Barang kloter ini sudah pernah diberangkatkan sebelumnya.');
        }

        $request->validate([
            'tracking_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $defaultNotes = $batch1->backorder_action === 'no_backorder'
            ? 'Pengiriman Barang: Pasokan telah diberangkatkan (Pemenuhan Sebagian Tanpa Backorder).'
            : 'Pengiriman Kloter 1: Barang pasokan telah diberangkatkan ke dapur UMKM.';

        $notes = $request->input('tracking_notes') ?: $defaultNotes;

        DB::transaction(function () use ($order, $batch1, $notes) {
            $orderCode = strtoupper(substr($order->id, 0, 8));

            $batch1->update([
                'shipped_at' => now(),
                'fulfillment_status' => $batch1->backorder_action === 'no_backorder' ? 'KIRIM_PARSIAL_NO_BACKORDER' : 'DIKIRIM',
                'tracking_notes' => $notes,
            ]);

            $order->update([
                'order_status' => Order::STATUS_PARTIALLY_SHIPPED,
            ]);

            $batch1Amount = 0;
            foreach ($order->items as $item) {
                $batch1Amount += (float) $item->quantity_delivered * (float) $item->unit_price;
            }

            $invoiceNotes = $batch1->backorder_action === 'no_backorder'
                ? 'Tagihan Pengiriman Parsial (Sisa Ditiadakan / No Backorder Disetujui)'
                : 'Tagihan Pengiriman Batch 1 (Sisa Pasokan Di-Backorder)';

            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'shipment_batch' => 1,
                ],
                [
                    'shipment_confirmation_id' => $batch1->id,
                    'invoice_number' => "INV/{$orderCode}/B1",
                    'quantity_billed' => (float) $batch1->shipped_quantity,
                    'amount' => $batch1Amount,
                    'payment_status' => 'UNPAID',
                    'notes' => $invoiceNotes,
                ]
            );
        });

        $msg = $batch1->backorder_action === 'no_backorder'
            ? '✓ Pesanan ('.number_format($batch1->shipped_quantity, 0).' kg) berhasil diberangkatkan! Tagihan resmi diterbitkan & UMKM dapat mengonfirmasi penerimaan barang.'
            : '✓ Kloter 1 ('.number_format($batch1->shipped_quantity, 0).' kg) berhasil diberangkatkan! Tagihan Invoice Batch 1 resmi diterbitkan.';

        return redirect()->back()->with('success', $msg);
    }

    public function kirimBackorder(Request $request, Order $order)
    {
        $order->load(['items.supplierProduct', 'shipmentConfirmations']);

        $totalRemaining = $order->items->sum('quantity_remaining');

        if ($totalRemaining <= 0) {
            return redirect()->back()->with('error', 'Pesanan ini tidak memiliki sisa barang yang perlu di-backorder.');
        }

        $batch1 = $order->shipmentConfirmations->firstWhere('shipment_batch', 1);
        if ($batch1 && $batch1->backorder_approval_status === 'rejected') {
            return redirect()->back()->with('error', 'Pengajuan Backorder ditolak oleh pihak UMKM. Sisa pesanan telah dibatalkan.');
        }
        if ($batch1 && $batch1->backorder_approval_status === 'pending') {
            return redirect()->back()->with('error', 'Pengajuan Backorder masih menunggu persetujuan dari pihak UMKM.');
        }

        $request->validate([
            'tracking_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $notes = $request->input('tracking_notes') ?: 'Pengiriman Kloter 2: Pemenuhan sisa barang Backorder.';

        DB::transaction(function () use ($order, $totalRemaining, $notes) {
            $orderCode = strtoupper(substr($order->id, 0, 8));

            $shipment2 = ShipmentConfirmation::create([
                'order_id' => $order->id,
                'shipment_batch' => 2,
                'shipped_quantity' => $totalRemaining,
                'backorder_action' => 'none',
                'fulfillment_status' => 'DIKIRIM_SISA_BACKORDER',
                'shipped_at' => now(),
                'tracking_notes' => $notes,
            ]);

            $batch2Amount = 0;
            foreach ($order->items as $item) {
                $batch2Amount += (float) $item->quantity_remaining * (float) $item->unit_price;

                $item->update([
                    'quantity_delivered' => $item->quantity,
                    'quantity_remaining' => 0,
                    'fulfillment_item_status' => 'fulfilled',
                ]);
            }

            $order->update([
                'order_status' => Order::STATUS_SHIPPED,
            ]);

            Payment::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'shipment_batch' => 2,
                ],
                [
                    'shipment_confirmation_id' => $shipment2->id,
                    'invoice_number' => "INV/{$orderCode}/B2",
                    'quantity_billed' => $totalRemaining,
                    'amount' => $batch2Amount,
                    'payment_status' => 'UNPAID',
                    'notes' => 'Tagihan Pemenuhan Sisa Backorder (Batch 2)',
                ]
            );
        });

        return redirect()->back()->with('success', '✓ Sisa barang Backorder (Batch 2) berhasil dikirim & Invoice Batch 2 resmi diterbitkan!');
    }
}
