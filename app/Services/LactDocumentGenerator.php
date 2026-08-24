<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Men-generate dokumen LACT (Laporan Commissioning Test) per LOP PT2 dari
 * template lact_template.docx (merge-field ${tag}) menggunakan PhpWord.
 *
 * Struktur LACT mengikuti persis 15-halaman contoh PDF yang dikirim user:
 *   - Laporan Commisioning Test (cover section + narasi single-signer WASPANG + ttd)
 *   - Lampiran Bill Of Quantity Commisioning Test
 *   - Lampiran Evident Pekerjaan (6 slot foto tetap: ODP Tampak Depan, Sebelum
 *     Expand, Material Tiba, Progress, Material Terpasang, Sesudah Expand)
 *   - Lampiran Evident Hasil Ukur OPM (grid Port N, dinamis sesuai rasio
 *     splitter LOP -- pakai logika sama persis dengan BautDocumentGenerator)
 *   - Distribusi ODC (OUT) (dBm) -- 1 slot foto
 *   - Lampiran Data Pengukuran OPM -- dikosongkan (sesuai keputusan: "seperti
 *     BAUT"), cuma tag umum (proyek/kontrak/dst) & ttd yang keisi
 *   - Lampiran Mancore -- 1 slot foto tunggal (sesuai keputusan: "seperti BAUT")
 * "Lampiran KML" TIDAK diisi (skip, sama seperti BAUT skip KML/ABD) -- tag
 * umum yang dipakai berulang tetap keisi karena TemplateProcessor mengisi
 * SEMUA occurrence dari tag yang sama di seluruh dokumen.
 *
 * Tidak ada section OTDR terpisah di LACT (berbeda dari BAUT) -- dikonfirmasi
 * lewat pdftotext + render visual 15 halaman contoh: TOC menyebut "Hasil Ukur
 * OPM & OTDR" tapi isinya cuma OPM ports + Distribusi ODC, tidak ada halaman
 * OTDR tersendiri, jadi template LACT tidak punya tag otdr_*.
 */
class LactDocumentGenerator
{
    private const IMG_WIDE = ['width' => 260, 'height' => 180, 'ratio' => false];
    private const IMG_OPM = ['width' => 150, 'height' => 110, 'ratio' => false];

    /** Tag teks sederhana yang di-setValue apa adanya. */
    private const SIMPLE_FIELDS = [
        'proyek', 'kontrak', 'surat_pesanan', 'witel', 'lokasi',
        'tempat_tanggal', 'nama_penandatangan', 'nik_penandatangan',
        'jabatan_penandatangan', 'sehubungan_dengan', 'status_pelaksanaan',
        'status_diterima', 'status_kelayakan',
    ];

    /** Urutan tetap 6 slot Lampiran Evident Pekerjaan (bukan cloneRow -- fixed grid). */
    private const EVIDEN_PEKERJAAN_SLOTS = ['a', 'b', 'c', 'd', 'e', 'f'];

    public function generate(string $templatePath, string $outputPath, array $data): void
    {
        $tp = new TemplateProcessor($templatePath);

        $this->fillSimpleFields($tp, $data);
        $this->fillBoqTable($tp, $data['boq_items'] ?? []);
        $this->fillEvidenPekerjaanGrid($tp, $data['eviden_pekerjaan_photos'] ?? []);
        $this->fillOpmGrid($tp, $data['opm_photos'] ?? [], (int) ($data['opm_slot_count'] ?? 1));
        $this->fillDistribusiOdc($tp, $data['distribusi_odc_photo'] ?? null);
        $this->fillSingleSlot($tp, 'mancore', $data['mancore_photo'] ?? null);

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $tp->saveAs($outputPath);
    }

    /**
     * Sama persis dengan BautDocumentGenerator::resolveOpmSlotCount -- LACT
     * pakai LOP/survey PT2 yang sama, rasio expand splitter yang sama.
     */
    public static function resolveOpmSlotCount(?string $mode, ?string $subModeA): int
    {
        return BautDocumentGenerator::resolveOpmSlotCount($mode, $subModeA);
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
     * Tabel BOQ: identik dengan BAUT (1 baris template di-clone sesuai
     * jumlah item).
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
     * Grid Lampiran Evident Pekerjaan: 6 slot TETAP (bukan dinamis/cloneRow
     * seperti eviden BAUT), caption tiap slot sudah statis di template
     * (ODP Tampak Depan, Sebelum Expand, Material Tiba, Progress, Material
     * Terpasang, Sesudah Expand). $photos diindeks 0-5 mengikuti urutan itu.
     */
    private function fillEvidenPekerjaanGrid(TemplateProcessor $tp, array $photos): void
    {
        foreach (self::EVIDEN_PEKERJAAN_SLOTS as $i => $slot) {
            $imgTag = "eviden_img_{$slot}";
            $photo = $photos[$i] ?? null;

            if ($photo && !empty($photo['path']) && is_file($photo['path'])) {
                $tp->setImageValue($imgTag, array_merge(self::IMG_WIDE, ['path' => $photo['path']]));
            } else {
                $tp->setValue($imgTag, $this->esc('(belum ada foto)'));
            }
        }
    }

    /**
     * Grid OPM: identik dengan BautDocumentGenerator::fillOpmGrid (2 slot
     * per baris, cloneRow sesuai opm_slot_count, label "Port N").
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
     * Kotak "Distribusi ODC (OUT) (dBm)": identik dengan BAUT, 1 slot foto
     * tunggal, judul kotak statis di template.
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

        $tp->setValue($imgTag, $this->esc($emptyCaption !== '' ? '(belum ada foto)' : ''));
        $tp->setValue($numTag, $this->esc($emptyCaption));
    }
}
