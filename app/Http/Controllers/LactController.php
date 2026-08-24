<?php

namespace App\Http\Controllers;

use App\Models\BautGeneratePt2;
use App\Models\LactGeneratePt2;
use App\Models\Pt2BoqItem;
use App\Models\Pt2Evidence;
use App\Models\Pt2Lop;
use App\Models\SurveyPt2;
use App\Services\BautDocumentGenerator;
use App\Services\LactDocumentGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LactController extends Controller
{
    private const SIMPLE_FIELDS = [
        'proyek', 'kontrak', 'surat_pesanan', 'witel', 'lokasi',
        'tempat_tanggal', 'nama_penandatangan', 'nik_penandatangan',
        'jabatan_penandatangan', 'sehubungan_dengan', 'status_pelaksanaan',
        'status_diterima', 'status_kelayakan',
    ];

    /** 6 slot foto tetap Lampiran Evident Pekerjaan, urutan = urutan caption di template. */
    private const EVIDEN_PEKERJAAN_SLOTS = [
        'eviden_pekerjaan_a' => 'ODP Tampak Depan',
        'eviden_pekerjaan_b' => 'Sebelum Expand',
        'eviden_pekerjaan_c' => 'Material Tiba',
        'eviden_pekerjaan_d' => 'Progress',
        'eviden_pekerjaan_e' => 'Material Terpasang',
        'eviden_pekerjaan_f' => 'Sesudah Expand',
    ];

    /**
     * Sama persis dengan BautController::EVIDENCE_TYPE_MAP -- pool eviden di
     * editor LACT bersumber dari eviden PT2 yang sama dengan BAUT, jadi
     * pengelompokan & label item-nya juga sama.
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
     * Halaman editor LACT untuk 1 LOP. $id = id_pt2_lop.
     *
     * LACT baru bisa digenerate SETELAH BAUT untuk LOP ybs sudah final --
     * tombol "Generate LACT" muncul di baris final pada Hasil Generate BAUT,
     * jadi editor ini mensyaratkan record BautGeneratePt2 berstatus final.
     */
    public function editor($id)
    {
        $lop = Pt2Lop::with(['project', 'boqItems'])->findOrFail($id);

        $gate = $this->checkGates($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return redirect()
                ->route('admin.pt2.baut.editor', $lop->id_pt2_lop)
                ->with('error', $gate['message']);
        }

        $opmSlotCount = $this->computeOpmSlotCount($lop);

        $draft = LactGeneratePt2::where('pt2_lop_id', $lop->id_pt2_lop)
            ->orderByDesc('id_lact_generate')
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
                'witel' => $lop->branch ?? '',
                'lokasi' => $lop->sto ?? '',
            ],
            array_fill_keys(self::SIMPLE_FIELDS, ''),
            $fieldValues
        );

        if ($draft) {
            $photoSlots = (object) ($draft->photo_slots ?? []);
        } else {
            // Draft baru: auto-isi slot OPM dari eviden redaman_port approved
            // (persis seperti BAUT -- LOP yang sama, eviden ukur yang sama).
            // 6 slot Lampiran Evident Pekerjaan TIDAK auto-isi (dipilih manual
            // lewat drag & drop dari pool, sama seperti slot Eviden Uji
            // Terima di BAUT).
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

        return view('admin.pt2.lact-editor', [
            'lop' => $lop,
            'project' => $lop->project,
            'draft' => $draft,
            'fieldValues' => $fieldValues,
            'boqItems' => $boqItems,
            'evidenceGroups' => $evidenceGroups,
            'photoSlots' => $photoSlots,
            'opmSlotCount' => $opmSlotCount,
            'simpleFields' => self::SIMPLE_FIELDS,
            'evidenPekerjaanSlots' => self::EVIDEN_PEKERJAAN_SLOTS,
        ]);
    }

    public function saveDraft(Request $request, $id)
    {
        $lop = Pt2Lop::findOrFail($id);
        $gate = $this->checkGates($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return back()->with('error', $gate['message']);
        }

        $draft = $this->upsertDraft($request, $lop);

        return back()->with('success', 'Draft LACT berhasil disimpan.')->with('lact_id', $draft->id_lact_generate);
    }

    public function generate(Request $request, $id)
    {
        $lop = Pt2Lop::with('project')->findOrFail($id);
        $gate = $this->checkGates($lop->id_pt2_lop);
        if (!$gate['allowed']) {
            return back()->with('error', $gate['message']);
        }

        $draft = $this->upsertDraft($request, $lop);

        $templatePath = storage_path('app/lact-templates/lact_template.docx');
        if (!is_file($templatePath)) {
            return back()->with('error', 'Template LACT belum tersedia di server (storage/app/lact-templates/lact_template.docx).');
        }

        $genData = $draft->field_values;
        $genData['boq_items'] = $draft->boq_snapshot ?? [];
        $genData['opm_slot_count'] = $draft->opm_slot_count;

        $slots = $draft->photo_slots ?? [];

        // PENTING: jangan array_filter/array_values di sini -- 6 slot Evident
        // Pekerjaan posisinya TETAP (caption sudah statis di template per
        // posisi a..f), jadi slot kosong harus tetap null di index-nya
        // masing2, bukan digeser rapat seperti eviden_photos BAUT.
        $genData['eviden_pekerjaan_photos'] = array_map(
            fn ($key) => $this->resolveSlot($slots[$key] ?? null),
            array_keys(self::EVIDEN_PEKERJAAN_SLOTS)
        );

        $genData['distribusi_odc_photo'] = $this->resolveSlot($slots['distribusi_odc'] ?? null);
        $genData['mancore_photo'] = $this->resolveSlot($slots['mancore'] ?? null);

        $opmPhotos = [];
        for ($i = 1; $i <= $draft->opm_slot_count; $i++) {
            $opmPhotos[] = $this->resolveSlot($slots["opm_{$i}"] ?? null);
        }
        $genData['opm_photos'] = $opmPhotos;

        $fileName = 'lact_' . $lop->id_pt2_lop . '_' . now()->format('YmdHis') . '.docx';
        $relativePath = "lact/{$lop->id_pt2_lop}/{$fileName}";
        $outputPath = storage_path('app/public/' . $relativePath);

        (new LactDocumentGenerator())->generate($templatePath, $outputPath, $genData);

        $draft->update([
            'status' => 'final',
            'generated_file_path' => $relativePath,
            'generated_at' => now(),
            'generated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.pt2.lact.show', ['lactId' => $draft->id_lact_generate, 'autodownload' => 1])
            ->with('success', 'Dokumen LACT berhasil digenerate.');
    }

    public function index(Request $request)
    {
        $query = LactGeneratePt2::with(['lop.project', 'generatedBy'])
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

        return view('admin.pt2.lact-results', compact('records'));
    }

    public function show($lactId)
    {
        $record = LactGeneratePt2::with(['lop.project'])->findOrFail($lactId);

        if ($record->status !== 'final') {
            return redirect()->route('admin.pt2.lact.editor', $record->pt2_lop_id);
        }

        $docxUrl = $record->generated_file_path
            ? Storage::disk('public')->url($record->generated_file_path)
            : null;

        return view('admin.pt2.lact-preview', compact('record', 'docxUrl'));
    }

    public function download($lactId)
    {
        $record = LactGeneratePt2::findOrFail($lactId);

        if (!$record->generated_file_path || !Storage::disk('public')->exists($record->generated_file_path)) {
            abort(404, 'File LACT tidak ditemukan.');
        }

        $downloadName = 'LACT_' . ($record->lop->lop_name ?? $record->pt2_lop_id) . '.docx';

        return Storage::disk('public')->download($record->generated_file_path, $downloadName);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function groupEvidencesByType($evidences): array
    {
        $typeToStep = [];
        foreach (self::EVIDENCE_TYPE_MAP as $step => $types) {
            foreach ($types as $type => $label) {
                $typeToStep[$type] = [$step, $label];
            }
        }

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

    /** Identik dengan BautController::computeOpmSlotCount. */
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

    private function redamanPortEvidences(Pt2Lop $lop)
    {
        return Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)
            ->where('status', 'approved')
            ->where('evidence_type', 'redaman_port')
            ->orderBy('id_pt2_evidence')
            ->get();
    }

    /**
     * 2 syarat sebelum LACT bisa diedit/di-generate:
     *  1) semua eviden PT2 LOP ybs sudah approved (sama seperti gate BAUT)
     *  2) BAUT untuk LOP ybs sudah di-generate final (LACT adalah dokumen
     *     lanjutan yang tombolnya muncul di baris final Hasil Generate BAUT)
     */
    private function checkGates($lopId): array
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
                'message' => "Generate LACT belum bisa dilakukan: masih ada {$notApproved} eviden yang belum berstatus approved.",
            ];
        }

        $bautFinal = BautGeneratePt2::where('pt2_lop_id', $lopId)
            ->where('status', 'final')
            ->exists();

        if (!$bautFinal) {
            return [
                'allowed' => false,
                'message' => 'Generate LACT belum bisa dilakukan: dokumen BAUT untuk LOP ini belum di-generate (final). Generate BAUT terlebih dahulu.',
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    private function upsertDraft(Request $request, Pt2Lop $lop): LactGeneratePt2
    {
        $fieldValues = [];
        foreach (self::SIMPLE_FIELDS as $field) {
            $fieldValues[$field] = (string) $request->input("field.{$field}", '');
        }

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

        $draft = LactGeneratePt2::where('pt2_lop_id', $lop->id_pt2_lop)
            ->where('status', 'draft')
            ->orderByDesc('id_lact_generate')
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
        return LactGeneratePt2::create($attrs);
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
