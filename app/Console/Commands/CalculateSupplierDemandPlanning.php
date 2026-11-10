<?php

namespace App\Console\Commands;

use App\Models\SupplierProfile;
use App\Services\Planning\SupplierDemandPlanningService;
use Illuminate\Console\Command;

class CalculateSupplierDemandPlanning extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supplier-demand:calculate 
                            {supplier_id? : ID UUID pemasok spesifik (opsional)}
                            {--horizon=7_DAYS : Horizon proyeksi (7_DAYS, 30_DAYS, 90_DAYS)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kalkulasi agregasi demand UMKM, Safety Stock, Reorder Point, dan Rekomendasi PO Pemasok';

    /**
     * Execute the console command.
     */
    public function handle(SupplierDemandPlanningService $planningService): int
    {
        $supplierId = $this->argument('supplier_id');
        $horizon = (string) $this->option('horizon');

        if ($supplierId) {
            $suppliers = SupplierProfile::where('id', $supplierId)->get();
        } else {
            $suppliers = SupplierProfile::all();
        }

        if ($suppliers->isEmpty()) {
            $this->warn('Tidak ada data pemasok ditemukan untuk dikalkulasi.');

            return self::SUCCESS;
        }

        $this->info("Memulai kalkulasi perencanaan persediaan & demand untuk {$suppliers->count()} pemasok (Horizon: {$horizon})...");

        $bar = $this->output->createProgressBar($suppliers->count());
        $bar->start();

        foreach ($suppliers as $supplier) {
            try {
                $planningService->generateForecastAndPlanning($supplier, $horizon);
            } catch (\Throwable $e) {
                $this->error("\nGagal memproses pemasok {$supplier->company_name}: ".$e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Kalkulasi perencanaan persediaan dan demand pemasok berhasil diselesaikan.');

        return self::SUCCESS;
    }
}
