<?php

namespace App\Console\Commands;

use App\Models\Evidence;
use App\Models\Lop;
use App\Models\ProjectAssignment;
use App\Models\ProjectStage;
use App\Services\ProjectActivityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Section AM -- Penyesuaian status_progress LOP lama (sebelum refactor
 * 11-tahap, migration 2026_09_08_090300_convert_lops_status_progress_to_project_stages).
 *
 * Migration konversi pertama menyamaratakan SEMUA LOP lama yang berstatus
 * enum 'preparation' langsung jadi 'persiapan_instalasi' (lihat catatan di
 * migration itu) -- asumsi paling aman saat itu, TAPI tidak akurat untuk LOP
 * yang sebenarnya sudah lebih jauh (instalasi/pengukuran/finishing) sebelum
 * refactor jalan, atau yang justru belum pernah di-assign waspang sama
 * sekali (harusnya 'inisiasi', bukan mulai dari 'persiapan_instalasi').
 *
 * Command ini TIDAK menebak-nebak status_progress LOP baru (yang sudah
 * lewat sub-step Survey/Perizinan/Material Delivery via flow baru) --
 * assigned LOP HANYA dimajukan (tidak pernah dimundurkan) kalau bukti bukti
 * eviden LAMA (stage 'persiapan'/'instalasi'/'pengukuran'/'finishing',
 * format yang sama dipakai WaspangController::isProjectReadyUt()) menunjukkan
 * posisi yang LEBIH JAUH dari sequence yang tersimpan sekarang.
 *
 * Aturan (hasil konfirmasi user, lihat Section AM di ANALISA_REFACTOR_PERSIAPAN.md):
 * 1. LOP yang BELUM di-assign waspang sama sekali (tidak ada baris pro_assign
 *    dengan waspang_id terisi) -> dipaksa ke 'inisiasi', apa pun status
 *    sekarang (dianggap data yang belum pernah benar-benar berjalan).
 * 2. LOP yang SUDAH di-assign tapi belum ada eviden sama sekali -> 'survey'
 *    (konsisten dengan ProjectController::assignWaspang() yang memajukan
 *    LOP baru dari inisiasi ke survey saat di-assign).
 * 3. LOP yang sudah di-assign DAN punya eviden -> dihitung ulang dari
 *    eviden lama (persiapanDone/instalasiDone/pengukuranDone/finishingDone),
 *    HANYA dimajukan kalau hasil hitungan lebih jauh dari sequence sekarang.
 * 4. LOP berstatus HOLD/DROP/FI-OGP Golive/Golive TIDAK disentuh sama sekali.
 * 5. LOP PT2 (jalur terpisah, Pt2Lop/pt2_lops) TIDAK disentuh sama sekali.
 *
 * Default = DRY RUN (hanya menampilkan daftar perubahan, tidak menulis apa
 * pun ke database). Jalankan dengan --apply untuk benar-benar menyimpan.
 *
 * Contoh pemakaian:
 *   php artisan lops:sync-legacy-status              # preview / dry-run
 *   php artisan lops:sync-legacy-status --project=123 # preview 1 project saja
 *   php artisan lops:sync-legacy-status --apply       # benar-benar simpan
 */
class SyncLegacyLopStatusProgress extends Command
{
    protected $signature = 'lops:sync-legacy-status
        {--apply : Benar-benar simpan perubahan. Tanpa opsi ini, command hanya menampilkan preview (dry-run).}
        {--project= : Batasi ke satu id_project saja (untuk uji coba sebelum jalan ke semua data).}';

    protected $description = 'Sesuaikan status_progress LOP lama (pra-refactor 11-tahap): belum di-assign -> inisiasi, sudah ada eviden -> tahap yang sesuai (dry-run secara default)';

    /** Status yang sama sekali tidak disentuh -- sudah final/dijeda. */
    private const SKIPPED_STATUSES = ['hold', 'drop', 'fi_ogp_golive', 'golive'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $onlyProjectId = $this->option('project');

        $stagesByCode = ProjectStage::pluck('sequence', 'code');

        $query = Lop::with(['project', 'project.assignments', 'project.evidences', 'project.boqItems'])
            ->whereNotIn('status_progress', self::SKIPPED_STATUSES);

        if ($onlyProjectId) {
            $query->where('project_id', $onlyProjectId);
        }

        $lops = $query->get();

        $changes = [];
        $skippedPt2 = 0;
        $skippedNoProject = 0;

        foreach ($lops as $lop) {
            $project = $lop->project;

            if (! $project) {
                $skippedNoProject++;

                continue;
            }

            $programSap = strtoupper($lop->program_sap ?? '');
            $isPt2 = str_contains($programSap, 'PT2') || str_contains($programSap, 'PT-2') || str_contains($programSap, 'PT 2');

            if ($isPt2) {
                $skippedPt2++;

                continue;
            }

            $currentCode = $lop->status_progress;
            $currentSequence = $currentCode === 'drm'
                ? ($stagesByCode['perizinan'] ?? 4)
                : ($stagesByCode[$currentCode] ?? 0);

            $isAssigned = $project->assignments->contains(fn (ProjectAssignment $a) => ! empty($a->waspang_id));

            if (! $isAssigned) {
                if ($currentCode !== 'inisiasi') {
                    $changes[] = [
                        'lop' => $lop,
                        'project' => $project,
                        'from' => $currentCode,
                        'to' => 'inisiasi',
                        'reason' => 'Belum ada waspang yang di-assign ke project ini',
                    ];
                }

                continue;
            }

            [$computedCode, $reason] = $this->computeTierFromLegacyEvidence($project->evidences, $project->boqItems);
            $computedSequence = $stagesByCode[$computedCode] ?? 0;

            if ($computedSequence > $currentSequence) {
                $changes[] = [
                    'lop' => $lop,
                    'project' => $project,
                    'from' => $currentCode,
                    'to' => $computedCode,
                    'reason' => $reason,
                ];
            }
        }

        $this->info('Total LOP diperiksa (di luar Hold/Drop/FI-OGP Golive/Golive): '.$lops->count());
        $this->info("Dilewati -- PT2: {$skippedPt2}, tanpa project: {$skippedNoProject}");
        $this->newLine();

        if (empty($changes)) {
            $this->info('Tidak ada LOP yang perlu disesuaikan. Semua status_progress sudah konsisten dengan assignment & eviden yang tersimpan.');

            return self::SUCCESS;
        }

        $rows = array_map(function (array $c) {
            return [
                $c['lop']->id_lop,
                $c['project']->id_project,
                $c['project']->project_name ?? '-',
                $c['from'],
                '->',
                $c['to'],
                $c['reason'],
            ];
        }, $changes);

        $this->table(
            ['id_lop', 'id_project', 'Nama Project', 'Dari', '', 'Ke', 'Alasan'],
            $rows
        );

        $this->newLine();
        $this->info(count($changes).' LOP akan disesuaikan statusnya.');

        if (! $apply) {
            $this->warn('DRY RUN -- belum ada yang disimpan. Jalankan ulang dengan --apply untuk benar-benar menyimpan perubahan di atas.');

            return self::SUCCESS;
        }

        $this->newLine();

        if (! $this->confirm('Simpan '.count($changes).' perubahan status_progress di atas ke database sekarang?', false)) {
            $this->warn('Dibatalkan. Tidak ada yang disimpan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                /** @var Lop $lop */
                $lop = $c['lop'];
                // FIX (permintaan user, log durasi per staging): lewat
                // advanceStage() jg supaya lop_stage_histories konsisten,
                // bukan cuma status_progress live-nya yang berubah.
                $lop->advanceStage($c['to'], null, 'Sync legacy status_progress (php artisan lops:sync-legacy-status)');

                ProjectActivityService::log([
                    'project_id' => $c['project']->id_project,
                    'lop_id' => $lop->id_lop,
                    'user_id' => null,
                    'activity_type' => 'sync_legacy_status_progress',
                    'title' => 'Penyesuaian Status Progress (Data Lama)',
                    'description' => "Status progress disesuaikan otomatis dari '{$c['from']}' ke '{$c['to']}' via php artisan lops:sync-legacy-status --apply. Alasan: {$c['reason']}",
                    'status_before' => $c['from'],
                    'status_after' => $c['to'],
                    'stage' => $c['to'],
                    'meta' => ['source' => 'lops:sync-legacy-status'],
                ]);
            }
        });

        $this->info(count($changes).' LOP berhasil disesuaikan dan dicatat di riwayat aktivitas project.');

        return self::SUCCESS;
    }

    /**
     * Hitung tahap LOP dari jejak eviden LAMA saja (stage
     * 'persiapan'/'instalasi'/'pengukuran'/'finishing') -- format yang sama
     * persis dipakai WaspangController::isProjectReadyUt(), supaya hasilnya
     * konsisten dengan logic "Ready UT" yang sudah berjalan. SENGAJA tidak
     * memakai Project::progressSummary() supaya tidak ikut ke-anchor ke
     * status_progress yang sedang kita koreksi (progressSummary() sebagian
     * shortcut-nya bergantung pada $sequence saat ini).
     *
     * @return array{0: string, 1: string} [kode tahap, alasan singkat]
     */
    private function computeTierFromLegacyEvidence($evidences, $boqItems): array
    {
        $barangTibaApproved = $evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->where('status', 'approved')
            ->isNotEmpty();

        $perizinanApproved = $evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->where('status', 'approved')
            ->isNotEmpty();

        $persiapanDone = $barangTibaApproved && $perizinanApproved;

        $hasPersiapanEvidence = $evidences
            ->where('stage', 'persiapan')
            ->whereIn('evidence_type', ['barang_tiba', 'perizinan'])
            ->isNotEmpty();

        $materialBoqItems = $boqItems->filter(function ($boq) {
            return ($boq->quantity_plan !== null)
                && (str_starts_with((string) $boq->designator, 'M-')
                    || optional($boq->designatorData)->type === 'material');
        });

        $boqTotal = $materialBoqItems->count();

        $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->isNotEmpty();
        })->count();

        $instalasiDone = $boqTotal > 0 && $boqApproved >= $boqTotal;

        $pengukuranDone =
            $evidences->where('stage', 'pengukuran')->where('evidence_type', 'otdr')->where('status', 'approved')->isNotEmpty()
            && $evidences->where('stage', 'pengukuran')->where('evidence_type', 'opm')->where('status', 'approved')->isNotEmpty()
            && $evidences->where('stage', 'pengukuran')->where('evidence_type', 'kedalaman')->where('status', 'approved')->isNotEmpty();

        $finishingDone = $evidences->where('stage', 'finishing')->where('status', 'approved')->isNotEmpty();

        if ($finishingDone) {
            return ['finishing', 'Sudah ada eviden Finishing yang disetujui'];
        }

        if ($instalasiDone && $pengukuranDone) {
            return ['finishing', 'Instalasi & Pengukuran (OTDR/OPM/Kedalaman) sudah lengkap disetujui, siap lanjut Finishing'];
        }

        if ($instalasiDone) {
            return ['pengukuran', 'Semua item material Instalasi sudah disetujui, eviden Pengukuran (OTDR/OPM/Kedalaman) belum lengkap'];
        }

        if ($persiapanDone) {
            return ['instalasi', 'Eviden Barang Tiba & Perizinan sudah disetujui, eviden Instalasi (progress BOQ) belum lengkap/belum ada'];
        }

        if ($hasPersiapanEvidence) {
            return ['persiapan_instalasi', 'Sudah ada eviden Barang Tiba/Perizinan diupload, tapi belum lengkap disetujui'];
        }

        return ['survey', 'Sudah di-assign waspang tapi belum ada eviden sama sekali'];
    }
}
