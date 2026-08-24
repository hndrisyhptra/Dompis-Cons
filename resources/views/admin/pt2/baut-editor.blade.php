@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto space-y-4 px-4 py-6" x-data="bautEditor({
        lopId: {{ (int) $lop->id_pt2_lop }},
        saveUrl: '{{ route('admin.pt2.baut.saveDraft', $lop->id_pt2_lop) }}',
        generateUrl: '{{ route('admin.pt2.baut.generate', $lop->id_pt2_lop) }}',
        fieldValues: @js($fieldValues),
        photoSlots: @js($photoSlots),
        opmSlotCount: {{ (int) $opmSlotCount }},
        evidenceGroups: @js($evidenceGroups),
    })">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Editor Dokumen BAUT</h1>
            <p class="text-sm text-gray-500">
                {{ $lop->lop_name }} · PID: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $project->pid ?? '-' }}</span>
                · IHLD: <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ $lop->id_ihld ?? '-' }}</span>
            </p>
        </div>
        <a href="{{ route('admin.pt2.mancore', $lop->id_pt2_lop) }}" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition shrink-0">
            ← Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm font-bold">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:items-start">

        {{-- MAIN COLUMN --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Data Teks BAUT --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-4">Data Dokumen (Header &amp; Berita Acara)</h3>
                <p class="text-xs text-gray-400 mb-4">Kop / letterhead sengaja dikosongkan di template &mdash; isi manual di Word setelah download. Field di bawah ini akan mengisi isi dokumen.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach([
                        'proyek' => 'Proyek', 'kontrak' => 'Kontrak (No.)', 'surat_pesanan' => 'Surat Pesanan',
                        'district' => 'District', 'lokasi' => 'Lokasi', 'tempat_tanggal' => 'Tempat, Tanggal (ttd)',
                    ] as $key => $label)
                        <div>
                            <label class="text-xs font-black text-slate-500">{{ $label }}</label>
                            <input type="text" x-model="fields.{{ $key }}" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                        </div>
                    @endforeach

                    <div>
                        <label class="text-xs font-black text-slate-500">Tanggal Uji Terima</label>
                        <input type="text" x-model="fields.tanggal_uji_terima" placeholder="cth: 20 Agustus 2026" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-500">Keputusan Uji Terima</label>
                        <select x-model="fields.keputusan_uji_terima" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                            <option value="">Pilih...</option>
                            <option value="DITERIMA">DITERIMA</option>
                            <option value="DITOLAK">DITOLAK</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-slate-500">Uraian Pekerjaan</label>
                        <input type="text" x-model="fields.uraian_pekerjaan" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="text-xs font-black text-slate-500">Nama TIM UJI TERIMA (Telkom Infrastruktur)</label>
                        <input type="text" x-model="fields.nama_tii" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-500">NIK (Telkom Infrastruktur)</label>
                        <input type="text" x-model="fields.nik_tii" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-500">Nama TIM UJI TERIMA (Telkom Akses)</label>
                        <input type="text" x-model="fields.nama_akses" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-black text-slate-500">NIK (Telkom Akses)</label>
                        <input type="text" x-model="fields.nik_akses" class="mt-1 w-full h-11 rounded-xl border-slate-300 text-sm">
                    </div>
                </div>
            </div>

            {{-- BOQ (read only, dari data project) --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider">Lampiran BOQ Uji Terima</h3>
                    <span class="text-[11px] font-bold text-gray-400">{{ count($boqItems) }} item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-[10px] uppercase font-bold text-gray-500">
                            <tr>
                                <th class="p-3">No</th><th class="p-3">Designator</th><th class="p-3">Item Pekerjaan</th><th class="p-3">Satuan</th><th class="p-3 text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($boqItems as $i => $item)
                                <tr>
                                    <td class="p-3">{{ $i + 1 }}</td>
                                    <td class="p-3 font-bold">{{ $item['designator'] }}</td>
                                    <td class="p-3">{{ $item['item_name'] }}</td>
                                    <td class="p-3">{{ $item['unit'] }}</td>
                                    <td class="p-3 text-center font-black text-indigo-600">{{ $item['qty'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-6 text-center text-gray-400 text-sm">Belum ada item BOQ pada LOP ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Lampiran Eviden Pekerjaan --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-1">Lampiran Eviden Pekerjaan</h3>
                <p class="text-xs text-gray-400 mb-3">Opsional &mdash; boleh dikosongkan salah satu atau kedua slot kalau tidak ada foto yang relevan.</p>
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="key in ['eviden_a','eviden_b']" :key="key">
                        <div class="dropzone" :class="photoSlots[key] ? 'has-photo' : ''"
                             @dragover.prevent @drop.prevent="dropOn(key, $event)">
                            <template x-if="photoSlots[key]">
                                <div class="relative group">
                                    <img :src="photoSlots[key].url" class="w-full h-32 object-cover rounded-xl">
                                    <button type="button" @click="clearSlot(key)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                                </div>
                            </template>
                            <template x-if="!photoSlots[key]">
                                <div class="h-32 flex items-center justify-center text-xs text-gray-400 font-bold text-center px-2">
                                    Tarik foto eviden ke sini<br><span x-text="key === 'eviden_a' ? '(Slot A)' : '(Slot B)'"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Lampiran Hasil Ukur OPM --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider">Lampiran Hasil Ukur OPM</h3>
                    <span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-lg">
                        <span x-text="opmSlotCount"></span> titik (otomatis dari jumlah eviden Step 3)
                    </span>
                </div>
                <p class="text-xs text-gray-400 mb-3">Jumlah slot mengikuti jumlah eviden foto "Eviden Hasil Ukur (Redaman &amp; Port)" Step 3 yang sudah approved, dan slot-nya sudah otomatis terisi foto tersebut secara berurutan. Geser (drag) foto lain dari pool di samping kalau perlu diganti/disusun ulang.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <template x-for="n in opmSlotCount" :key="'opm_'+n">
                        <div class="dropzone" :class="photoSlots['opm_'+n] ? 'has-photo' : ''"
                             @dragover.prevent @drop.prevent="dropOn('opm_'+n, $event)">
                            <template x-if="photoSlots['opm_'+n]">
                                <div class="relative group">
                                    <img :src="photoSlots['opm_'+n].url" class="w-full h-24 object-cover rounded-xl">
                                    <button type="button" @click="clearSlot('opm_'+n)" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                                </div>
                            </template>
                            <template x-if="!photoSlots['opm_'+n]">
                                <div class="h-24 flex items-center justify-center text-xs text-gray-400 font-bold text-center px-1">
                                    Port <span x-text="n"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Lampiran Hasil Ukur OTDR, Distribusi ODC & Mancore --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-3">Lampiran Hasil Ukur OTDR</h3>
                    <div class="dropzone" :class="photoSlots['otdr'] ? 'has-photo' : ''" @dragover.prevent @drop.prevent="dropOn('otdr', $event)">
                        <template x-if="photoSlots['otdr']">
                            <div class="relative group">
                                <img :src="photoSlots['otdr'].url" class="w-full h-32 object-cover rounded-xl">
                                <button type="button" @click="clearSlot('otdr')" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                            </div>
                        </template>
                        <template x-if="!photoSlots['otdr']">
                            <div class="h-32 flex items-center justify-center text-xs text-gray-400 font-bold">Tarik foto OTDR ke sini</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-3">Distribusi ODC (OUT)</h3>
                    <div class="dropzone" :class="photoSlots['distribusi_odc'] ? 'has-photo' : ''" @dragover.prevent @drop.prevent="dropOn('distribusi_odc', $event)">
                        <template x-if="photoSlots['distribusi_odc']">
                            <div class="relative group">
                                <img :src="photoSlots['distribusi_odc'].url" class="w-full h-32 object-cover rounded-xl">
                                <button type="button" @click="clearSlot('distribusi_odc')" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                            </div>
                        </template>
                        <template x-if="!photoSlots['distribusi_odc']">
                            <div class="h-32 flex items-center justify-center text-xs text-gray-400 font-bold text-center px-2">Tarik foto Distribusi ODC (OUT) ke sini</div>
                        </template>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                    <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-3">Lampiran Mancore</h3>
                    <div class="dropzone" :class="photoSlots['mancore'] ? 'has-photo' : ''" @dragover.prevent @drop.prevent="dropOn('mancore', $event)">
                        <template x-if="photoSlots['mancore']">
                            <div class="relative group">
                                <img :src="photoSlots['mancore'].url" class="w-full h-32 object-cover rounded-xl">
                                <button type="button" @click="clearSlot('mancore')" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/60 text-white text-xs opacity-0 group-hover:opacity-100 transition">×</button>
                            </div>
                        </template>
                        <template x-if="!photoSlots['mancore']">
                            <div class="h-32 flex items-center justify-center text-xs text-gray-400 font-bold">Tarik foto mancore ke sini</div>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        {{-- SIDEBAR: EVIDENCE POOL + ACTIONS (1 sticky panel, ikut discroll) --}}
        <div class="space-y-4 lg:sticky lg:top-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
                <h3 class="text-xs font-black uppercase text-gray-400 tracking-wider mb-1">Pool Eviden Approved</h3>
                <p class="text-xs text-gray-400 mb-3">Tarik (drag) foto dari sini ke slot yang sesuai di sebelah kiri. Dikelompokkan per step &amp; nama item persis seperti di menu approval PT2 &mdash; klik judul step untuk buka/tutup.</p>

                <div class="space-y-2 max-h-[480px] overflow-y-auto pr-1">
                    <template x-for="(step, stepIdx) in evidenceGroups" :key="step.step_label">
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
                            <button type="button" @click="toggleStep(step.step_label)"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 transition text-left">
                                <span class="text-[11px] font-black uppercase text-gray-700 dark:text-gray-200" x-text="step.step_label"></span>
                                <span class="flex items-center gap-2 shrink-0">
                                    <span class="text-[10px] font-bold text-gray-400" x-text="stepPhotoCount(step) + ' foto'"></span>
                                    <span class="text-gray-400 text-xs transition-transform" :class="isStepOpen(stepIdx, step.step_label) ? 'rotate-180' : ''">▾</span>
                                </span>
                            </button>
                            <div x-show="isStepOpen(stepIdx, step.step_label)" class="p-3 space-y-3">
                                <template x-for="group in step.groups" :key="step.step_label + '|' + group.label">
                                    <div>
                                        <p class="text-[10px] font-black uppercase text-indigo-500 mb-1.5" x-text="group.label"></p>
                                        <div class="grid grid-cols-3 gap-2">
                                            <template x-for="ev in group.items" :key="ev.id">
                                                <img :src="ev.url" draggable="true"
                                                     @dragstart="dragStart(ev, $event)"
                                                     @dragend="dragged = null"
                                                     :title="group.label"
                                                     class="w-full h-16 object-cover rounded-lg cursor-grab border border-gray-200 hover:ring-2 hover:ring-indigo-400 transition">
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="evidenceGroups.length === 0">
                        <p class="text-xs text-gray-400 text-center py-6">Tidak ada eviden approved.</p>
                    </template>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm space-y-2">
                <button type="button" @click="saveDraft()" :disabled="saving"
        class="w-full h-11 rounded-xl bg-white border border-slate-300 text-slate-700 text-sm font-black hover:bg-slate-50 transition disabled:opacity-50 flex items-center justify-center gap-2">
    <span x-show="!saving" class="flex items-center gap-2">
       <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save-icon lucide-save"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
        Simpan Draft
    </span>

    <span x-show="saving" class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 animate-spin">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-6.219-8.56" />
        </svg>
        Menyimpan...
    </span>
</button>


<button type="button" @click="generateDoc()" :disabled="saving"
        class="w-full h-11 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-black transition disabled:opacity-50 flex items-center justify-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-down-icon lucide-file-down"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>
    Generate &amp; Download
</button>
            </div>
        </div>
    </div>
</div>

<style>
    .dropzone { border-radius: 0.75rem; border-width: 2px; border-style: dashed; border-color: #cbd5e1; transition: all .15s ease; }
    .dark .dropzone { border-color: #334155; }
    .dropzone.has-photo { border-style: solid; border-color: transparent; }
</style>

<script>
function bautEditor(cfg) {
    return {
        lopId: cfg.lopId,
        saveUrl: cfg.saveUrl,
        generateUrl: cfg.generateUrl,
        fields: cfg.fieldValues,
        // cfg.photoSlots datang dari server sebagai object JSON ({}); jaga-jaga
        // kalau ternyata array kosong ([]) tetap dipaksa jadi object biasa,
        // supaya JSON.stringify() saat submit tidak diam-diam membuang isinya
        // (array hanya menyimpan properti berindeks angka saat di-stringify).
        photoSlots: Array.isArray(cfg.photoSlots) ? {} : (cfg.photoSlots || {}),
        opmSlotCount: cfg.opmSlotCount,
        evidenceGroups: cfg.evidenceGroups || [],
        saving: false,
        dragged: null,
        // Accordion per step di pool eviden -- step pertama (yang ada
        // fotonya) otomatis terbuka, sisanya tertutup supaya pool tidak
        // langsung panjang penuh 5 step.
        openSteps: {},

        // Dipanggil otomatis oleh Alpine saat komponen di-init (bukan di
        // dalam x-show/:class supaya tidak mutasi state di tengah render).
        init() {
            this.evidenceGroups.forEach((step, idx) => {
                this.openSteps[step.step_label] = idx === 0;
            });
        },
        stepPhotoCount(step) {
            return step.groups.reduce((n, g) => n + g.items.length, 0);
        },
        isStepOpen(stepIdx, stepLabel) {
            return !!this.openSteps[stepLabel];
        },
        toggleStep(stepLabel) {
            this.openSteps[stepLabel] = !this.openSteps[stepLabel];
        },

        dragStart(ev, e) {
            this.dragged = ev;
            e.dataTransfer.effectAllowed = 'copy';
            // Wajib diisi -- tanpa setData(), beberapa browser (Firefox/Safari)
            // tidak akan memicu event 'drop' sama sekali di elemen tujuan.
            e.dataTransfer.setData('text/plain', String(ev.id));
        },
        dropOn(slotKey, e) {
            let data = this.dragged;
            if (!data) {
                // Fallback kalau state 'dragged' sempat hilang (mis. reload
                // parsial) tapi dataTransfer masih membawa id eviden.
                const id = parseInt(e.dataTransfer.getData('text/plain'), 10);
                data = this.evidenceGroups
                    .flatMap(step => step.groups)
                    .flatMap(g => g.items)
                    .find(ev => ev.id === id);
            }
            if (!data) return;
            this.photoSlots[slotKey] = { evidence_id: data.id, url: data.url, caption: '' };
            this.dragged = null;
        },
        clearSlot(slotKey) {
            delete this.photoSlots[slotKey];
        },

        // Submit sungguhan (bukan fetch) supaya redirect dari server (mis.
        // back() untuk draft, atau redirect ke halaman preview setelah
        // generate) berjalan natural lewat navigasi browser -- menghindari
        // salah GET ke route yang cuma menerima POST.
        submitForm(url) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';

            const addField = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value ?? '';
                form.appendChild(input);
            };

            addField('_token', document.querySelector('meta[name="csrf-token"]').content);
            Object.keys(this.fields).forEach(k => addField('field[' + k + ']', this.fields[k]));
            addField('opm_slot_count', this.opmSlotCount);
            addField('photo_slots', JSON.stringify(this.photoSlots));

            document.body.appendChild(form);
            form.submit();
        },
        saveDraft() {
            this.saving = true;
            this.submitForm(this.saveUrl);
        },
        generateDoc() {
            this.saving = true;
            this.submitForm(this.generateUrl);
        },
    };
}
</script>
@endsection
