<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step "Pengukuran" (Opsi B): sebelum lanjut ke Finishing, kelima item ini
 * harus "beres" -- beres berarti salah satu dari (a) ada eviden ter-upload,
 * atau (b) waspang menandai "tidak ada" lewat radio button.
 *
 * evidences.file_path bersifat NOT NULL, jadi opsi "tidak ada" tidak bisa
 * direpresentasikan sebagai baris evidences kosong -- karena itu dibuatkan
 * tabel kecil terpisah ini, 1 baris per item per LOP (upsert lewat unique
 * key lop_id+item_key).
 *
 * BACKFILL LEGACY: sebelum refactor ini, Pengukuran cuma pass-through alias
 * dari selesainya Instalasi (lihat Project::progressSummary() versi lama:
 * `$pengukuranDone = $instalasiDone` -- tidak ada pengecekan independen).
 * Jadi LOP yang statusnya SEKARANG sudah Finishing/lebih hampir pasti belum
 * pernah benar-benar mengisi kelima item ini. Dikonfirmasi pemilik project:
 * backfill otomatis kelima item itu jadi "tidak ada/legacy" untuk LOP-LOP
 * tersebut, supaya data lama tidak keblokir gate Pengukuran yang baru.
 */
return new class extends Migration
{
    public const ITEMS = ['otdr', 'file_sor', 'opm', 'kedalaman', 'eviden_lainnya'];

    /**
     * status_progress yang dianggap "sudah lewat Pengukuran" untuk keperluan
     * backfill legacy di atas.
     */
    private const LEGACY_STATUSES = ['finishing', 'fi_ogp_golive', 'golive'];

    public function up(): void
    {
        Schema::create('lop_measurement_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lop_id');
            $table->string('item_key', 30);
            $table->boolean('is_not_applicable')->default(false);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->unsignedBigInteger('checked_by')->nullable();
            $table->timestamps();

            $table->unique(['lop_id', 'item_key']);
            $table->foreign('lop_id')->references('id_lop')->on('lops')->onDelete('cascade');
            $table->foreign('evidence_id')->references('id_evidence')->on('evidences')->onDelete('set null');
            $table->foreign('checked_by')->references('id_user')->on('users')->onDelete('set null');
        });

        $this->backfillLegacyMeasurementChecks();
    }

    private function backfillLegacyMeasurementChecks(): void
    {
        $now = now();

        $legacyLopIds = DB::table('lops')
            ->whereIn('status_progress', self::LEGACY_STATUSES)
            ->pluck('id_lop');

        foreach ($legacyLopIds as $lopId) {
            foreach (self::ITEMS as $itemKey) {
                DB::table('lop_measurement_checks')->insertOrIgnore([
                    'lop_id' => $lopId,
                    'item_key' => $itemKey,
                    'is_not_applicable' => true,
                    'note' => 'Backfill otomatis (legacy) -- LOP ini sudah melewati tahap Finishing sebelum step Pengukuran diberlakukan sebagai gate nyata, sehingga item ini ditandai "tidak ada" secara otomatis.',
                    'evidence_id' => null,
                    'checked_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_measurement_checks');
    }
};
