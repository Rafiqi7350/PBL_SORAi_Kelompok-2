<?php

namespace App\Console\Commands;

use App\Models\SupplierProfile;
use App\Services\Performance\SupplierPerformanceTrackerService;
use Illuminate\Console\Command;

class CalculateSupplierPerformanceScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier-performance:calculate {supplier_id? : Optional ID profil Supplier tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kalkulasi Skor Indikator Performa Pemasok (OTD, Pemenuhan Kuota, Kualitas Komoditas)';

    /**
     * Execute the console command.
     */
    public function handle(SupplierPerformanceTrackerService $trackerService): int
    {
        $supplierId = $this->argument('supplier_id');

        $query = SupplierProfile::query();
        if ($supplierId) {
            $query->where('id', $supplierId);
        }

        $suppliers = $query->get();

        if ($suppliers->isEmpty()) {
            $this->warn('Tidak ada profil Supplier yang ditemukan.');

            return self::SUCCESS;
        }

        $this->info("Memulai kalkulasi skor performa untuk {$suppliers->count()} pemasok...");

        $bar = $this->output->createProgressBar($suppliers->count());
        $bar->start();

        foreach ($suppliers as $supplier) {
            $trackerService->calculate($supplier);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Kalkulasi skor performa pemasok berhasil diselesaikan.');

        return self::SUCCESS;
    }
}
