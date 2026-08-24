<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Men-generate dokumen BAUT (Berita Acara Uji Terima) per LOP PT2 dari
 * template baut_template.docx (merge-field ${tag}) menggunakan PhpWord.
 *
 * Cakupan section pada fase ini (sesuai keputusan scope):
 *   - BA Uji Terima (cover + narasi + keputusan + ttd)
 *   - Lampiran BOQ Uji Terima
 *   - Lampiran Eviden Pekerjaan
 *   - Lampiran Hasil Ukur OPM & OTDR (termasuk halaman cover Data Pengukuran OPM)
 *   - Lampiran Mancore
 * "Lampiran KML" dan "Berita Acara ABD" TIDAK diisi (skip), tag sharing
 * (proyek/kontrak/dst) di section tsb tetap ikut terisi karena tag yang sama
 * dipakai berulang di seluruh dokumen, sisanya dibiarkan apa adanya.
 *
 * Ukuran gambar dalam poin (PhpWord default unit setImageValue = px).
 * Nilai berikut adalah estimasi awal berdasarkan lebar kolom template dan
 * PERLU disesuaikan setelah verifikasi visual pertama pada dokumen nyata.
 */
class BautDocumentGenerator
{
    private const IMG_WIDE = ['width' => 260, 'height' => 180, 'ratio' => false];
    private const IMG_OPM = ['width' => 150, 'height' => 110, 'ratio' => false];

    /** Tag teks sederhana yang di-setValue apa adanya. */
    private const SIMPLE_FIELDS = [
        'proyek', 'kontrak', 'surat_pesanan', 'district', 'lokasi',
        'tempat_tanggal', 'nama_tii', 'nik_tii', 'nama_akses', 'nik_akses',
        'tanggal_uji_terima', 'uraian_pekerjaan', 'keputusan_uji_terima',
    ];

    public function generate(string $templatePath, string $outputPath, array $data): void
    {
        $tp = new TemplateProcessor($templatePath);

        $this->fillSimpleFields($tp, $data);
        $this->fillBoqTable($tp, $data['boq_items'] ?? []);
        $this->fillEvidenGrid($tp, $data['eviden_photos'] ?? []);
        $this->fillOpmGrid($tp, $data['opm_photos'] ?? [], (int) ($data['opm_slot_count'] ?? 1));
        $this->fillDistribusiOdc($tp, $data['distribusi_odc_photo'] ?? null);
        $this->fillSingleSlot($tp, 'otdr', $data['otdr_photo'] ?? null);
        $this->fillSingleSlot($tp, 'mancore', $data['mancore_photo'] ?? null);

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tp->saveAs($outputPath);
    }

    /**
     * Menentukan jumlah slot foto OPM berdasarkan mode/sub_mode survey LOP
     * (bukan input manual admin yang lepas dari data).
     *
     * - Mode B ("Expand Add Splitter 1:8")              -> 8 slot
     * - Mode A + sub_mode_a "Expand Splitter 1:16"       -> 16 slot
     * - Mode A + sub_mode_a "Ganti ODP", Mode C, lainnya -> 1 slot (default)
     */
    public static function resolveOpmSlotCount(?string $mode, ?string $subModeA): int
    {
        if ($mode === 'B') {
            return 8;
        }

        if ($mode === 'A' && $subModeA === 'Expand Splitter 1:16') {
            return 16;
        }

        return 1;
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    private function fillSimpleFields(TemplateProcessor $tp, array $data): void
    {
        foreach (self::SIMPLE_FIELDS as $field) {
            $tp->setValue($field, $this->esc($data[$field] ?? ''));
        }
    }

    /**
     * Tabel BOQ: 1 baris template (${boq_no} dst) di-clone sebanyak jumlah
     * item. cloneRow mengganti tag jadi ${boq_no#1}, ${boq_no#2}, dst
     * (termasuk baris pertama, yang ikut dinomori #1).
     */
    private function fillBoqTable(TemplateProcessor $tp, array $items): void
    {
        $count = max(count($items), 1);
        $tp->cloneRow('boq_no', $count);

        for ($i = 0; $i < $count; $i++) {
            $n = $i + 1;
            $item = $items[$i] ?? null;

            $tp->setValue("boq_no#{$n}", $item ? (string) $n : '');
            $tp->setValue("boq_designator#{$n}", $this->esc($item['designator'] ?? ''));
            $tp->setValue("boq_item#{$n}", $this->esc($item['item_name'] ?? ''));
            $tp->setValue("boq_unit#{$n}", $this->esc($item['unit'] ?? ''));
            $tp->setValue("boq_qty#{$n}", $this->esc((string) ($item['qty'] ?? '')));
        }
    }

    /**
     * Grid Eviden Pekerjaan: 2 slot foto per baris template, di-clone sesuai
     * jumlah foto yang dipilih admin (dibulatkan ke atas per 2).
     */
    private function fillEvidenGrid(TemplateProcessor $tp, array $photos): void
    {
        $rows = max((int) ceil(count($photos) / 2), 1);
        $tp->cloneRow('eviden_img_a', $rows);

        foreach (range(1, $rows) as $rowIdx) {
            foreach (['a', 'b'] as $j => $slot) {
                $flatIdx = ($rowIdx - 1) * 2 + $j;
                $this->setPhotoSlot($tp, "eviden_img_{$slot}#{$rowIdx}", "eviden_num_{$slot}#{$rowIdx}", $photos[$flatIdx] ?? null, self::IMG_WIDE, $flatIdx + 1);
            }
        }
    }

    /**
     * Grid OPM: 2 slot foto per baris template (kolom "Port N" berlabel bold
     * di atas foto), di-clone sesuai opm_slot_count (rasio expand splitter
     * LOP ybs), bukan sekadar jumlah foto yang sudah diupload -- supaya slot
     * yang belum ada fotonya tetap tampil sebagai "belum ada foto" untuk
     * diisi admin. Label port SELALU "Port N" (bukan caption dari eviden).
     */
    private function fillOpmGrid(TemplateProcessor $tp, array $photos, int $slotCount): void
    {
        $slotCount = max($slotCount, 1);
        $rows = max((int) ceil($slotCount / 2), 1);
        $tp->cloneRow('opm_img_a', $rows);

        foreach (range(1, $rows) as $rowIdx) {
            foreach (['a', 'b'] as $j => $slot) {
                $flatIdx = ($rowIdx - 1) * 2 + $j;
                $imgTag = "opm_img_{$slot}#{$rowIdx}";
                $labelTag = "opm_label_{$slot}#{$rowIdx}";

                if ($flatIdx >= $slotCount) {
                    // Melebihi jumlah slot yang dibutuhkan rasio splitter -> kosongkan.
                    $tp->setValue($imgTag, '');
                    $tp->setValue($labelTag, '');
                    continue;
                }

                $photo = $photos[$flatIdx] ?? null;
                $tp->setValue($labelTag, $this->esc('Port ' . ($flatIdx + 1)));

                if ($photo && !empty($photo['path']) && is_file($photo['path'])) {
                    $tp->setImageValue($imgTag, array_merge(self::IMG_OPM, ['path' => $photo['path']]));
                } else {
                    $tp->setValue($imgTag, $this->esc('(belum ada foto)'));
                }
            }
        }
    }

    /**
     * Kotak "Distribusi ODC (OUT) (dBm)": 1 slot foto tunggal, judul kotak
     * statis di template (bukan merge tag) jadi cukup isi fotonya saja.
     */
    private function fillDistribusiOdc(TemplateProcessor $tp, ?array $photo): void
    {
        if ($photo && !empty($photo['path']) && is_file($photo['path'])) {
            $tp->setImageValue('distribusi_odc_img_a', array_merge(self::IMG_WIDE, ['path' => $photo['path']]));
            return;
        }

        $tp->setValue('distribusi_odc_img_a', $this->esc('(belum ada foto)'));
    }

    private function fillSingleSlot(TemplateProcessor $tp, string $prefix, ?array $photo): void
    {
        $this->setPhotoSlot($tp, "{$prefix}_img_a", "{$prefix}_num_a", $photo, self::IMG_WIDE, 1);
    }

    private function setPhotoSlot(TemplateProcessor $tp, string $imgTag, string $numTag, ?array $photo, array $size, int $index, string $emptyCaption = ''): void
    {
        if ($photo && !empty($photo['path']) && is_file($photo['path'])) {
            $tp->setImageValue($imgTag, array_merge($size, ['path' => $photo['path']]));
            $tp->setValue($numTag, $this->esc($photo['caption'] ?? ('Foto ' . $index)));
            return;
        }

        // Slot ada (sesuai jumlah yg dibutuhkan) tapi belum ada foto terpasang.
        $tp->setValue($imgTag, $this->esc($emptyCaption !== '' ? '(belum ada foto)' : ''));
        $tp->setValue($numTag, $this->esc($emptyCaption));
    }
}
