<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Partnership;
use App\Models\Payment;
use App\Models\SupplierProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RiwayatTransaksiController extends Controller
{
    /**
     * Tampilkan halaman Riwayat Transaksi Pemasok terintegrasi Supabase.
     */
    public function index(Request $request): View
    {
        // 1. Ambil profil pemasok yang sedang aktif/login
        $supplier = $this->getActiveSupplierProfile();

        $search = trim((string) $request->input('search', ''));
        $statusFilter = strtolower(trim((string) $request->input('status', 'all')));

        // 2. Query dasar: Hanya ambil data payment yang berelasi dengan supplier_id aktif
        $baseQuery = Payment::query();

        if ($supplier) {
            $baseQuery->whereHas('order.partnership', function ($q) use ($supplier) {
                $q->where('supplier_id', $supplier->id);
            });
        }

        // 3. Kalkulasi 2 Card Statistik Atas langsung dari database Supabase (agregasi 1 query)
        $paidStatusesSql = "'PAID', 'SETTLEMENT', 'CAPTURE', 'SUCCESS', 'LUNAS'";

        $stats = (clone $baseQuery)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN UPPER(COALESCE(payment_status, '')) IN ({$paidStatusesSql}) THEN amount ELSE 0 END), 0) as total_uang_didapat,
                COUNT(CASE WHEN UPPER(COALESCE(payment_status, '')) IN ({$paidStatusesSql}) THEN 1 END) as count_lunas,
                COALESCE(SUM(CASE WHEN UPPER(COALESCE(payment_status, '')) NOT IN ({$paidStatusesSql}) THEN amount ELSE 0 END), 0) as total_belum_dibayar,
                COUNT(CASE WHEN UPPER(COALESCE(payment_status, '')) NOT IN ({$paidStatusesSql}) THEN 1 END) as count_belum_dibayar,
                COUNT(*) as total_count
            ")
            ->first();

        $totalUangDidapat = (float) ($stats->total_uang_didapat ?? 0);
        $totalBelumDibayar = (float) ($stats->total_belum_dibayar ?? 0);
        $countLunas = (int) ($stats->count_lunas ?? 0);
        $countBelumDibayar = (int) ($stats->count_belum_dibayar ?? 0);
        $totalCount = (int) ($stats->total_count ?? 0);

        // 4. Query untuk Tabel Histori dengan Eager Loading relasi (Order, Partnership, UMKM, Komoditas)
        $tableQuery = (clone $baseQuery)
            ->with([
                'order.partnership.umkm',
                'order.items.supplierProduct.commodity',
                'shipmentConfirmation',
            ]);

        // Filter Status: 'all', 'paid', atau 'unpaid'
        if ($statusFilter === 'paid') {
            $tableQuery->whereRaw("UPPER(COALESCE(payment_status, '')) IN ({$paidStatusesSql})");
        } elseif ($statusFilter === 'unpaid') {
            $tableQuery->whereRaw("UPPER(COALESCE(payment_status, '')) NOT IN ({$paidStatusesSql})");
        }

        // Filter Pencarian: No. Invoice atau Nama Mitra UMKM
        if (! empty($search)) {
            $tableQuery->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ilike', "%{$search}%")
                    ->orWhereHas('order.partnership.umkm', function ($uq) use ($search) {
                        $uq->where('business_name', 'ilike', "%{$search}%")
                            ->orWhere('owner_name', 'ilike', "%{$search}%");
                    })
                    ->orWhereHas('order.items.supplierProduct.commodity', function ($cq) use ($search) {
                        $cq->where('name', 'ilike', "%{$search}%");
                    });
            });
        }

        // 5. Paginasi Database (10 item per halaman)
        $transactions = $tableQuery->latest('created_at')->paginate(10)->withQueryString();

        // 6. Siapkan data untuk fungsi Cetak Rekap (gunakan collection yang sudah dimuat jika 1 halaman agar hemat query & loading instan)
        if ($transactions->total() <= 10) {
            $allTransactionsForPrint = $transactions->getCollection()->map(
                fn (Payment $p) => $this->formatTransactionForExport($p)
            );
        } else {
            $allTransactionsForPrint = (clone $tableQuery)
                ->latest('created_at')
                ->limit(100)
                ->get()
                ->map(fn (Payment $p) => $this->formatTransactionForExport($p));
        }

        // Data Pemasok untuk Dokumen Formal Invoice & Rekap
        $supplierData = [
            'company_name' => $supplier?->company_name ?: 'Pemasok B2B Sorai',
            'owner_name' => $supplier?->owner_name ?: 'Pengelola Pemasok',
            'phone' => $supplier?->phone ?: '0812-3456-7890',
            'warehouse_address' => $supplier?->warehouse_address ?: 'Sentra Logistik Pergudangan B2B Sorai',
            'nib_number' => $supplier?->nib_number ?: '-',
        ];

        return view('supplier.riwayat-transaksi.index', [
            'supplier' => $supplier,
            'supplierData' => $supplierData,
            'transactions' => $transactions,
            'allTransactions' => $allTransactionsForPrint,
            'totalUangDidapat' => $totalUangDidapat,
            'totalBelumDibayar' => $totalBelumDibayar,
            'totalUangDidapatFormatted' => 'Rp '.number_format($totalUangDidapat, 0, ',', '.'),
            'totalBelumDibayarFormatted' => 'Rp '.number_format($totalBelumDibayar, 0, ',', '.'),
            'countLunas' => $countLunas,
            'countBelumDibayar' => $countBelumDibayar,
            'totalCount' => $totalCount,
            'search' => $search,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Helper untuk format seragam data transaksi ke frontend / fungsi cetak.
     *
     * @return array<string, mixed>
     */
    public function formatTransactionForExport(Payment $payment): array
    {
        $order = $payment->order;
        $umkm = $order?->partnership?->umkm;
        $firstItem = $order?->items?->first();
        $commodity = $firstItem?->supplierProduct?->commodity;

        $isPaid = $payment->isPaid();
        $amount = (float) $payment->amount;

        $dateObj = $payment->paid_at ?? $payment->transaction_time ?? $payment->created_at ?? now();
        $carbonDate = Carbon::parse($dateObj);

        $quantity = $payment->quantity_billed ?? $firstItem?->quantity ?? 0;
        $unit = $commodity?->unit ?? 'Kg';

        return [
            'id' => (string) $payment->id,
            'invoice_number' => $payment->invoice_number ?: ('INV/'.strtoupper(substr((string) ($order?->id ?? $payment->id), 0, 8)).'/B'.($payment->shipment_batch ?? 1)),
            'order_code' => 'ORD-'.$carbonDate->format('Y').'-'.strtoupper(substr((string) ($order?->id ?? $payment->id), 0, 6)),
            'date' => $carbonDate->locale('id')->translatedFormat('d M Y'),
            'time' => $carbonDate->format('H:i').' WIB',
            'customer_name' => $umkm?->business_name ?: ($umkm?->owner_name ?: 'Mitra UMKM B2B'),
            'owner_name' => $umkm?->owner_name ?: 'Pengelola UMKM',
            'customer_phone' => $umkm?->phone ?: '-',
            'customer_address' => $umkm?->address ?: 'Malang, Jawa Timur',
            'commodity_name' => $commodity?->name ?: 'Komoditas Pangan Segar',
            'quantity_formatted' => number_format((float) $quantity, 0, ',', '.').' '.$unit,
            'batch_label' => 'Batch '.($payment->shipment_batch ?? 1),
            'amount' => $amount,
            'amount_formatted' => 'Rp '.number_format($amount, 0, ',', '.'),
            'status' => $isPaid ? 'PAID' : 'UNPAID',
            'status_label' => $isPaid ? 'Lunas' : 'Belum Dibayar',
            'payment_method' => $payment->payment_method ?: ($isPaid ? 'Transfer Bank Escrow' : 'Menunggu Pembayaran (Midtrans)'),
            'paid_at' => $payment->paid_at ? Carbon::parse($payment->paid_at)->locale('id')->translatedFormat('d M Y, H:i').' WIB' : '-',
            'notes' => $payment->notes ?: 'Tagihan suplai bahan baku sesuai kontrak kemitraan.',
        ];
    }

    /**
     * Dapatkan profil supplier yang sedang aktif login.
     */
    private function getActiveSupplierProfile(): ?SupplierProfile
    {
        $user = Auth::user();

        if ($user && $user->role === 'supplier' && $user->supplierProfile) {
            return $user->supplierProfile;
        }

        $dummySupplierUser = User::where('role', 'supplier')->first();
        if ($dummySupplierUser && $dummySupplierUser->supplierProfile) {
            return $dummySupplierUser->supplierProfile;
        }

        return SupplierProfile::first();
    }
}
