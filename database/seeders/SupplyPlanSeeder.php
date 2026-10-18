<?php

namespace Database\Seeders;

use App\Models\Partnership;
use App\Models\SupplierProfile;
use App\Models\SupplyPlan;
use App\Models\UmkmProfile;
use Illuminate\Database\Seeder;

class SupplyPlanSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = SupplierProfile::take(3)->get();
        $umkms = UmkmProfile::take(3)->get();

        if ($suppliers->isEmpty() || $umkms->isEmpty()) {
            return;
        }

        $nextMonth = now()->addMonth()->format('Y-m');
        $twoMonthsAhead = now()->addMonths(2)->format('Y-m');

        // Plan 1: Daging Ayam Broiler Segar (Menunggu Konfirmasi)
        $partnership1 = Partnership::firstOrCreate(
            ['umkm_id' => $umkms[0]->id, 'supplier_id' => $suppliers[0]->id],
            ['submission_details' => 'Kolaborasi Perencanaan Pasokan Ayam Dapur', 'status' => 'active']
        );

        SupplyPlan::firstOrCreate(
            [
                'partnership_id' => $partnership1->id,
                'commodity_name' => 'Daging Ayam Broiler Segar',
                'period_month_year' => $nextMonth,
            ],
            [
                'estimated_quantity' => 350.00,
                'unit' => 'kg',
                'supplier_confirmation_status' => 'MENUNGGU_KONFIRMASI',
                'notes' => 'Kebutuhan dada fillet dingin suhu 4°C, pengiriman subuh jam 05:00 WIB 2x seminggu.',
            ]
        );

        // Plan 2: Telur Ayam Ras Grade A (Disetujui Penuh)
        SupplyPlan::firstOrCreate(
            [
                'partnership_id' => $partnership1->id,
                'commodity_name' => 'Telur Ayam Ras Grade A',
                'period_month_year' => $nextMonth,
            ],
            [
                'estimated_quantity' => 150.00,
                'unit' => 'kg',
                'supplier_confirmation_status' => 'DISETUJUI',
                'confirmed_quantity' => 150.00,
                'notes' => 'Telur utuh grade A cangkang bersih, packing tray karton.',
                'supplier_notes' => 'Kapasitas farm Blitar siap 100% untuk pasokan periode '.$nextMonth.'.',
            ]
        );

        // Plan 3: Cabai Rawit Merah (Disesuaikan Kapasitas)
        if (isset($suppliers[1])) {
            $partnership2 = Partnership::firstOrCreate(
                ['umkm_id' => $umkms[0]->id, 'supplier_id' => $suppliers[1]->id],
                ['submission_details' => 'Kolaborasi Pasokan Bumbu & Rempah', 'status' => 'active']
            );

            SupplyPlan::firstOrCreate(
                [
                    'partnership_id' => $partnership2->id,
                    'commodity_name' => 'Cabai Rawit Merah Super',
                    'period_month_year' => $twoMonthsAhead,
                ],
                [
                    'estimated_quantity' => 100.00,
                    'unit' => 'kg',
                    'supplier_confirmation_status' => 'DISESUAIKAN',
                    'confirmed_quantity' => 80.00,
                    'notes' => 'Cabai segar petik merah 90%, tangkai dipetik rapi.',
                    'supplier_notes' => 'Alokasi panen ladang Batu sanggup 80 kg karena berbagi kuota dengan mitra lain.',
                ]
            );
        }
    }
}
