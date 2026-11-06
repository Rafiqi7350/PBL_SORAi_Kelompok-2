<?php

namespace App\Console\Commands;

use App\Models\UmkmProfile;
use App\Services\Reliability\UmkmReliabilityScoreService;
use Illuminate\Console\Command;

class CalculateUmkmReliabilityScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reliability:calculate {umkm_id? : Optional ID profil UMKM tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kalkulasi Skor Keandalan Mitra UMKM dan pemeringkatan Term of Payment';

    /**
     * Execute the console command.
     */
    public function handle(UmkmReliabilityScoreService $scoreService): int
    {
        $umkmId = $this->argument('umkm_id');

        $query = UmkmProfile::query();
        if ($umkmId) {
            $query->where('id', $umkmId);
        }

        $umkms = $query->get();

        if ($umkms->isEmpty()) {
            $this->warn('Tidak ada profil UMKM yang ditemukan.');

            return self::SUCCESS;
        }

        $this->info("Memulai kalkulasi skor keandalan untuk {$umkms->count()} mitra UMKM...");

        $bar = $this->output->createProgressBar($umkms->count());
        $bar->start();

        foreach ($umkms as $umkm) {
            $score = $scoreService->calculate($umkm);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Kalkulasi skor keandalan selesai.');

        return self::SUCCESS;
    }
}
