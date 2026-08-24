<?php

namespace App\Http\Controllers;

use App\Models\BautGeneratePt2;
use App\Models\Pt2BoqItem;
use App\Models\Pt2Evidence;
use App\Models\Pt2Lop;
use App\Models\SurveyPt2;
use App\Services\BautDocumentGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BautController extends Controller
{
    private const SIMPLE_FIELDS = [
        'proyek', 'kontrak', 'surat_pesanan', 'district', 'lokasi',
        'tempat_tanggal', 'nama_tii', 'nik_tii', 'nama_akses', 'nik_akses',
        'tanggal_uji_terima', 'uraian_pekerjaan', 'keputusan_uji_terima',
    ];

    /**
     * Peta evidence_type -> label item persis seperti yang dipakai di
     * halaman approval eviden step 1-5 (review.blade.php, instalasi.blade.php,
     * redaman.blade.php, dismantle.blade.php) -- supaya pool eviden di editor
     * BAUT memakai istilah yang SAMA dengan yang admin lihat saat approve,
     * bukan sekadar nama stage generik. Step 1 mode-dependent (lihat
     * review.blade.php baris 11-30); step 5 (Mancore) belum punya halaman
     * review eviden sendiri jadi belum ada mapping-nya.
     *
     * PENTING: step 3 & step 4 sama-sama pakai stage='finishing' di DB
     * (bukan 'finish' vs 'finishing' seperti asumsi lama), jadi grouping
     * WAJIB berdasarkan evidence_type, bukan stage, supaya keduanya tidak
     * tercampur jadi satu grup.
     */
    private const EVIDENCE_TYPE_MAP = [
        1 => [
            'power_in' => 'Foto Eviden Power IN',
            'power_out' => 'Foto Eviden Power OUT',
            'base_tray_feeder' => 'Foto Eviden Base Tray Feeder',
            'base_tray_distribusi' => 'Foto Eviden Base Tray Distribusi',
            'power_in_feeder' => 'Foto Power IN Feeder',
            'power_out_splitter' => 'Foto Power OUT Splitter Ex',
            'survey' => 'Foto Eviden Survey Lapangan',
        ],
        2 => [
            'material' => 'Eviden Material / Barang Tiba',
            'progress_instalasi' => 'Eviden Progress Instalasi',
        ],
        3 => [
            'redaman_port' => 'Eviden Hasil Ukur (Redaman & Port)',
            'foto_lainnya' => 'Foto Tambahan / Lainnya (Opsional)',
        ],
        4 => [
            'odp' => 'Foto Eviden ODP Dibongkar',
            'splitter_1_2' => 'Foto Eviden Splitter 1:2',
            'splitter_1_4' => 'Foto Eviden Splitter 1:4',
            'splitter_1_8' => 'Foto Eviden Splitter 1:8',
            'splitter_1_16' => 'Foto Eviden Splitter 1:16',
        ],
        5 => [],
    ];

    private const STEP_TITLES = [
        1 => 'Step 1 — Persiapan / Survey',
        2 => 'Step 2 — Instalasi',
        3 => 'Step 3 — Redaman & Port',
        4 => 'Step 4 — Dismantle',
        5 => 'Step 5 — Mancore',
    ];

    /**
     * Halaman editor BAUT untuk 1 LOP. $id = id_pt2_lop.
     */
    public function editor($id)
    {
        $lop = Pt2Lop::with(['project', 'boqItems'])->findOrFail($id);

        $gate = $this->checkEvidenceGate($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return redirect()
                ->route('admin.pt2.mancore', $lop->id_pt2_lop)
                ->with('error', $gate['message']);
        }

        // Jumlah slot OPM sekarang MENGIKUTI jumlah eviden foto "Eviden Hasil
        // Ukur (Redaman & Port)" Step 3 yang sudah di-approve (bukan lagi
        // dari rasio splitter/mode survey) -- lihat computeOpmSlotCount().
        $opmSlotCount = $this->computeOpmSlotCount($lop);

        // Draft yang sudah ada untuk LOP ini (kalau pernah dibuat sebelumnya)
        $draft = BautGeneratePt2::where('pt2_lop_id', $lop->id_pt2_lop)
            ->orderByDesc('id_baut_generate')
            ->first();

        $evidenceGroups = $this->groupEvidencesByType(
            Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)
                ->where('status', 'approved')
                ->orderBy('evidence_type')
                ->get()
        );

        $boqItems = $lop->boqItems->map(function ($item) {
            return [
                'designator' => $item->designator,
                'item_name' => $item->item_name,
                'unit' => $item->unit,
                'qty' => $item->quantity_plan,
            ];
        })->values();

        $fieldValues = $draft->field_values ?? [];
        $fieldValues = array_merge(
            [
                'proyek' => $lop->project->project_name ?? '',
                'district' => $lop->branch ?? '',
                'lokasi' => $lop->sto ?? '',
            ],
            array_fill_keys(self::SIMPLE_FIELDS, ''),
            $fieldValues
        );

        if ($draft) {
            // Draft yang sudah pernah disimpan sebelumnya -- pakai persis
            // apa yang admin sudah atur (jangan timpa slot yang mungkin
            // sengaja dikosongkan/diganti manual).
            $photoSlots = (object) ($draft->photo_slots ?? []);
        } else {
            // Draft BARU (belum pernah disimpan sama sekali): auto-isi slot
            // OPM dari eviden "redaman_port" yang sudah approved, berurutan
            // sesuai approve -- supaya admin tidak perlu drag manual satu
            // per satu untuk kasus paling umum. Tetap bisa diganti/dipindah
            // manual lewat drag-and-drop di editor sebelum disimpan.
            $autoSlots = [];
            foreach ($this->redamanPortEvidences($lop) as $i => $ev) {
                $autoSlots['opm_' . ($i + 1)] = [
                    'evidence_id' => $ev->id_pt2_evidence,
                    'url' => Storage::disk('public')->url($ev->file_path),
                    'caption' => '',
                ];
            }
            $photoSlots = (object) $autoSlots;
        }

        return view('admin.pt2.baut-editor', [
            'lop' => $lop,
            'project' => $lop->project,
            'draft' => $draft,
            'fieldValues' => $fieldValues,
            'boqItems' => $boqItems,
            'evidenceGroups' => $evidenceGroups,
            // Selalu kirim sebagai object JSON ({}), bukan array ([]), walau
            // masih kosong -- supaya JS tidak keliru menganggapnya Array
            // (yang bikin JSON.stringify() diam-diam membuang semua isinya).
            'photoSlots' => $photoSlots,
            'opmSlotCount' => $opmSlotCount,
            'simpleFields' => self::SIMPLE_FIELDS,
        ]);
    }

    /**
     * Simpan draft (belum generate file .docx final).
     */
    public function saveDraft(Request $request, $id)
    {
        $lop = Pt2Lop::findOrFail($id);
        $gate = $this->checkEvidenceGate($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return back()->with('error', $gate['message']);
        }

        $draft = $this->upsertDraft($request, $lop);

        return back()->with('success', 'Draft BAUT berhasil disimpan.')->with('baut_id', $draft->id_baut_generate);
    }

    /**
     * Generate dokumen .docx final dari data editor.
     */
    public function generate(Request $request, $id)
    {
        $lop = Pt2Lop::with('project')->findOrFail($id);
        $gate = $this->checkEvidenceGate($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return back()->with('error', $gate['message']);
        }

        $draft = $this->upsertDraft($request, $lop);

        $templatePath = storage_path('app/baut-templates/baut_template.docx');
        if (!is_file($templatePath)) {
            return back()->with('error', 'Template BAUT belum tersedia di server (storage/app/baut-templates/baut_template.docx).');
        }

        $genData = $draft->field_values;
        $genData['boq_items'] = $draft->boq_snapshot ?? [];
        $genData['opm_slot_count'] = $draft->opm_slot_count;

        $slots = $draft->photo_slots ?? [];
        $genData['eviden_photos'] = array_values(array_filter([
            $this->resolveSlot($slots['eviden_a'] ?? null),
            $this->resolveSlot($slots['eviden_b'] ?? null),
        ]));
        $genData['otdr_photo'] = $this->resolveSlot($slots['otdr'] ?? null);
        $genData['mancore_photo'] = $this->resolveSlot($slots['mancore'] ?? null);
        $genData['distribusi_odc_photo'] = $this->resolveSlot($slots['distribusi_odc'] ?? null);

        $opmPhotos = [];
        for ($i = 1; $i <= $draft->opm_slot_count; $i++) {
            $opmPhotos[] = $this->resolveSlot($slots["opm_{$i}"] ?? null);
        }
        $genData['opm_photos'] = $opmPhotos;

        $fileName = 'baut_' . $lop->id_pt2_lop . '_' . now()->format('YmdHis') . '.docx';
        $relativePath = "baut/{$lop->id_pt2_lop}/{$fileName}";
        $outputPath = storage_path('app/public/' . $relativePath);

        (new BautDocumentGenerator())->generate($templatePath, $outputPath, $genData);

        $draft->update([
            'status' => 'final',
            'generated_file_path' => $relativePath,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.pt2.baut.show', ['bautId' => $draft->id_baut_generate, 'autodownload' => 1])
            ->with('success', 'Dokumen BAUT berhasil digenerate.');
    }

    /**
     * Daftar semua hasil generate BAUT (menu baru).
     */
    public function index(Request $request)
    {
        $query = BautGeneratePt2::with(['lop.project', 'generatedBy'])
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('lop', function ($q) use ($search) {
                $q->where('lop_name', 'like', "%{$search}%")
                  ->orWhere('id_ihld', 'like', "%{$search}%")
                  ->orWhereHas('project', function ($qp) use ($search) {
                      $qp->where('pid', 'like', "%{$search}%")
                         ->orWhere('project_name', 'like', "%{$search}%");
                  });
            });
        }

        $records = $query->paginate(12)->withQueryString();

        return view('admin.pt2.baut-results', compact('records'));
    }

    /**
     * Preview 1 hasil generate. Kalau masih draft, arahkan ke editor.
     */
    public function show($bautId)
    {
        $record = BautGeneratePt2::with(['lop.project'])->findOrFail($bautId);

        if ($record->status !== 'final') {
            return redirect()->route('admin.pt2.baut.editor', $record->pt2_lop_id);
        }

        // URL publik file .docx yang baru digenerate, dipakai JS (docx-preview)
        // di halaman ini untuk merender preview visualnya tanpa perlu download.
        $docxUrl = $record->generated_file_path
            ? Storage::disk('public')->url($record->generated_file_path)
            : null;

        return view('admin.pt2.baut-preview', compact('record', 'docxUrl'));
    }

    public function download($bautId)
    {
        $record = BautGeneratePt2::findOrFail($bautId);

        if (!$record->generated_file_path || !Storage::disk('public')->exists($record->generated_file_path)) {
            abort(404, 'File BAUT tidak ditemukan.');
        }

        $downloadName = 'BAUT_' . ($record->lop->lop_name ?? $record->pt2_lop_id) . '.docx';

        return Storage::disk('public')->download($record->generated_file_path, $downloadName);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Kelompokkan eviden approved per Step 1-5, dan di dalam tiap step
     * dipecah lagi per evidence_type persis seperti label item yang dipakai
     * di halaman approval eviden ybs (mis. "Eviden Material / Barang Tiba")
     * -- supaya pool foto di editor gampang dipilah per item, bukan 1 grid
     * campur aduk semua eviden dalam 1 step.
     *
     * evidence_type yang tidak dikenal (di luar EVIDENCE_TYPE_MAP) tetap
     * ditampilkan sebagai grup "Lainnya" di step 0 (paling atas) supaya
     * tidak ada foto approved yang hilang begitu saja dari pool.
     */
    private function groupEvidencesByType($evidences): array
    {
        // typeToStep[evidence_type] = [step, label]
        $typeToStep = [];
        foreach (self::EVIDENCE_TYPE_MAP as $step => $types) {
            foreach ($types as $type => $label) {
                $typeToStep[$type] = [$step, $label];
            }
        }

        // steps[step][type] = ['label' => .., 'items' => [...]]
        $steps = [];
        foreach ($evidences as $ev) {
            $type = (string) ($ev->evidence_type ?? '');
            [$step, $label] = $typeToStep[$type] ?? [0, $type !== '' ? "Lainnya ({$type})" : 'Lainnya'];

            if (!isset($steps[$step][$type])) {
                $steps[$step][$type] = ['label' => $label, 'items' => []];
            }

            $steps[$step][$type]['items'][] = [
                'id' => $ev->id_pt2_evidence,
                'evidence_type' => $ev->evidence_type,
                'stage' => $ev->stage,
                'url' => Storage::disk('public')->url($ev->file_path),
            ];
        }

        ksort($steps);

        $result = [];
        foreach ($steps as $step => $groups) {
            // Urutkan sub-grup mengikuti urutan deklarasi di EVIDENCE_TYPE_MAP
            // (sama seperti urutan tampil di halaman approval eviden ybs),
            // bukan alfabetis -- supaya mis. "Base Tray Feeder" tetap tampil
            // sebelum "Base Tray Distribusi" seperti di review.blade.php.
            $orderedKeys = array_keys(self::EVIDENCE_TYPE_MAP[$step] ?? []);
            uksort($groups, function ($a, $b) use ($orderedKeys) {
                $ia = array_search($a, $orderedKeys);
                $ib = array_search($b, $orderedKeys);
                $ia = $ia === false ? 999 : $ia;
                $ib = $ib === false ? 999 : $ib;
                return $ia <=> $ib ?: strcmp($a, $b);
            });

            $result[] = [
                'step_label' => self::STEP_TITLES[$step] ?? 'Lainnya',
                'groups' => array_values($groups),
            ];
        }

        return $result;
    }

    /**
     * Jumlah slot foto OPM = jumlah eviden "Eviden Hasil Ukur (Redaman &
     * Port)" Step 3 yang sudah di-approve untuk LOP ini -- supaya jumlah
     * port yang tampil di BAUT selalu sesuai jumlah foto ukur yang
     * benar-benar ada (bukan lagi estimasi dari rasio splitter/mode
     * survey). Kalau belum ada satupun eviden redaman_port ter-approve
     * (LOP baru / belum sempat upload), fallback ke estimasi lama
     * berdasarkan rasio splitter supaya editor tidak tampil kosong sama
     * sekali sebelum eviden diupload.
     */
    private function computeOpmSlotCount(Pt2Lop $lop): int
    {
        $count = $this->redamanPortEvidences($lop)->count();
        if ($count > 0) {
            return $count;
        }

        $survey = SurveyPt2::where('pt2_lop_id', $lop->id_pt2_lop)->first();

        return BautDocumentGenerator::resolveOpmSlotCount(
            $survey->mode ?? null,
            $survey->sub_mode_a ?? null
        );
    }

    /**
     * Eviden foto "Eviden Hasil Ukur (Redaman & Port)" Step 3 yang sudah
     * approved untuk 1 LOP, diurutkan sesuai urutan approve -- dipakai baik
     * untuk menghitung jumlah slot OPM maupun untuk auto-isi slot draft
     * baru (lihat editor()).
     */
    private function redamanPortEvidences(Pt2Lop $lop)
    {
        return Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)
            ->where('status', 'approved')
            ->where('evidence_type', 'redaman_port')
            ->orderBy('id_pt2_evidence')
            ->get();
    }

    private function checkEvidenceGate($lopId): array
    {
        $total = Pt2Evidence::where('pt2_lop_id', $lopId)->count();

        if ($total === 0) {
            return ['allowed' => false, 'message' => 'Belum ada eviden yang diupload untuk LOP ini.'];
        }

        $notApproved = Pt2Evidence::where('pt2_lop_id', $lopId)
            ->where('status', '!=', 'approved')
            ->count();

        if ($notApproved > 0) {
            return [
                'allowed' => false,
                'message' => "Generate BAUT belum bisa dilakukan: masih ada {$notApproved} eviden yang belum berstatus approved.",
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    private function upsertDraft(Request $request, Pt2Lop $lop): BautGeneratePt2
    {
        $fieldValues = [];
        foreach (self::SIMPLE_FIELDS as $field) {
            $fieldValues[$field] = (string) $request->input("field.{$field}", '');
        }

        // Dihitung ulang di server (bukan percaya nilai hidden input dari
        // form) supaya selalu konsisten dengan jumlah eviden redaman_port
        // yang benar-benar approved saat draft disimpan / digenerate.
        $opmSlotCount = $this->computeOpmSlotCount($lop);

        $boqSnapshot = Pt2BoqItem::where('pt2_lop_id', $lop->id_pt2_lop)
            ->get(['designator', 'item_name', 'unit', 'quantity_plan'])
            ->map(fn ($i) => [
                'designator' => $i->designator,
                'item_name' => $i->item_name,
                'unit' => $i->unit,
                'qty' => $i->quantity_plan,
            ])->values()->all();

        $photoSlots = json_decode($request->input('photo_slots', '{}'), true) ?: [];

        $draft = BautGeneratePt2::where('pt2_lop_id', $lop->id_pt2_lop)
            ->where('status', 'draft')
            ->orderByDesc('id_baut_generate')
            ->first();

        $attrs = [
            'pt2_lop_id' => $lop->id_pt2_lop,
            'pt2_project_id' => $lop->pt2_project_id,
            'field_values' => $fieldValues,
            'boq_snapshot' => $boqSnapshot,
            'opm_slot_count' => $opmSlotCount,
            'photo_slots' => $photoSlots,
            'updated_by' => auth()->id(),
        ];

        if ($draft) {
            $draft->update($attrs);
            return $draft->fresh();
        }

        $attrs['status'] = 'draft';
        return BautGeneratePt2::create($attrs);
    }

    private function resolveSlot(?array $slot): ?array
    {
        if (!$slot || empty($slot['evidence_id'])) {
            return null;
        }

        $evidence = Pt2Evidence::find($slot['evidence_id']);
        if (!$evidence || !Storage::disk('public')->exists($evidence->file_path)) {
            return null;
        }

        return [
            'path' => storage_path('app/public/' . $evidence->file_path),
            'caption' => $slot['caption'] ?? '',
        ];
    }
}
