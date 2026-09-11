# Analisa & Audit — Refactor Upload LOP + Sub-Step Persiapan (PT3/Reguler)

> **HANDOFF UNTUK CLAUDE — WAJIB BACA BAGIAN INI TERLEBIH DAHULU**
>
> Dokumen ini bersifat kronologis. Bagian A–R berisi audit, keputusan, implementasi, dan rencana pada saat masing-masing bagian ditulis. Beberapa rencana lama sudah diselesaikan atau dibatalkan. **Kondisi aplikasi yang berlaku saat ini adalah bagian “Ringkasan Kondisi Terkini” di bawah serta bagian S–T.** Jika ada pertentangan, kondisi terkini mengalahkan catatan historis.

Dokumen terakhir diperbarui **10 September 2026 (Asia/Jakarta)** setelah migration freeze dibuka, data direkonsiliasi, sumber status dipindahkan ke LOP, kolom status project induk dihapus, dashboard/filter/import diperbarui, dan smoke test aplikasi dijalankan.

---

## 0. Ringkasan Kondisi Terkini — Sumber Kebenaran untuk Handoff

### 0.1 Keputusan produk dan alur final

Alur Persiapan reguler/PT3 yang berlaku sekarang:

1. **Inisiasi** — PID dan BOQ diinput/import admin.
2. **Survey** — aktif setelah LOP di-assign ke Waspang.
3. **Perizinan** — Survey yang selesai langsung menuju Perizinan.
4. **Material Delivery**.
5. **Persiapan Instalasi**.
6. **Instalasi**.
7. **Pengukuran**.
8. **Finishing**.
9. **FI-OGP Golive**.
10. **Golive**.

Status khusus **Hold** dan **Drop** tetap tersedia. Nilai kode yang benar untuk Material Delivery adalah `material_delivery`.

**Proses DRM sudah dihapus dari alur aktif.** Jangan membuat kembali tombol, route, upload, atau transisi DRM. Master row `project_stages.code=drm` masih dipertahankan secara fisik untuk keamanan histori/foreign key, tetapi:

- dikecualikan oleh scope `ProjectStage::active()` dan `ProjectStage::sequential()`;
- disembunyikan dan dilindungi dari pengelolaan tahap aktif;
- nilai DRM historis dibaca sebagai Perizinan;
- finalisasi Survey langsung menulis `lops.status_progress = 'perizinan'`;
- evidence/kronologi DRM lama tidak dihapus karena merupakan histori.

Desain UI Waspang yang berlaku adalah desain **flat** dengan primary `#1565D8`, background `#F8FAFC`, card putih, tanpa gradient. Pertahankan desain ini kecuali user meminta perubahan baru.

### 0.2 Fitur Survey yang sudah dibangun

Pada accordion Survey:

- map menampilkan hasil KML/desain awal yang diunggah admin setelah input PID dan BOQ;
- tersedia aksi **Sesuai** dan **Redesign**;
- Redesign membuka halaman Site Survey untuk tagging tiang, membuat rute kabel, dan aktivitas desain lapangan;
- nama LOP pada survey otomatis mengikuti LOP yang sedang dikerjakan dan tidak boleh diubah manual;
- hasil desain terbaru menjadi map aktif, sedangkan desain/map lama tetap dipertahankan sebagai histori;
- setelah desain sesuai, user dapat masuk ke **Finalisasi Survey**;
- tombol **Update Kronologi** yang sempat duplikat sebelum Finalisasi Survey sudah dihapus; tombol universal kronologi tetap tersedia pada aktivitas yang relevan.

Finalisasi Survey:

- memakai BOQ plan sebagai referensi;
- hanya kategori designator `M` dan `J` yang dipakai;
- material/jasa dengan `pair_code` sama ditampilkan sebagai satu baris;
- menampilkan designator dan volume plan yang terkunci;
- volume survey wajib diisi;
- designator tambahan dapat ditambahkan dengan volume plan kosong;
- item tambahan dapat dihapus bila tidak sesuai;
- **Simpan Draf** menyimpan isian sementara;
- **Selesai Survey** menyimpan finalisasi dan memindahkan status ke Perizinan;
- project yang mempunyai lebih dari satu LOP ditolak oleh mutation Survey lama agar data tidak salah diarahkan ke LOP pertama.

Implementasi utama berada di:

- `app/Services/SurveyPreparationService.php`
- `app/Http/Controllers/WaspangController.php`
- `app/Http/Controllers/SurveyorController.php`
- `resources/views/waspang/partials/survey-workflow.blade.php`
- `resources/views/surveyor/show.blade.php`
- `tests/Feature/WaspangSurveyWorkflowTest.php`

### 0.3 Sumber status kanonik setelah refactor

Semua status pekerjaan sekarang berada di level LOP:

| Domain | Sumber kebenaran |
|---|---|
| Posisi proses reguler/PT3 | `lops.status_progress` |
| Persetujuan SDI reguler/PT3 | `lops.sdi_approval_status` |
| Golive reguler/PT3 | `lops.is_golive` |
| Bukti dan waktu Golive reguler | `lops.golive_evidence_path`, `lops.golive_at` |
| Posisi proses PT2 | `pt2_lops.status_progress` |
| Persetujuan SDI PT2 | `pt2_lops.sdi_approval_status` |
| Golive PT2 | `pt2_lops.is_golive` |

Kolom berikut **sudah dihapus secara fisik** dan tidak boleh diperkenalkan kembali sebagai sumber status:

- `projects.status`
- `projects.status_project`
- `projects.sdi_approval_status`
- `projects.is_golive`
- `projects.golive_evidence_path`
- `projects.golive_at`
- `pt2_projects.status`
- `pt2_projects.status_project`
- `pt2_projects.sdi_approval_status`
- `pt2_projects.is_golive`

`status_project` masih muncul pada beberapa controller/service hanya sebagai **alias input kompatibilitas** untuk URL/template lama. Alias tersebut dinormalisasi lalu ditulis ke `status_progress` LOP; tidak ada lagi penulisan ke project parent.

Status domain lain tidak ikut dihapus karena berbeda fungsi, antara lain:

- `lops.status_progress_before_hold` untuk kembali dari Hold/Drop;
- `lops.mapping_status` untuk hasil pemetaan import;
- status evidence, approval, survey, kendala, import process, GIS/CAD, BAUT, dan LACT;
- status user aktif/nonaktif.

### 0.4 Migrasi dan keadaan database live

Migration freeze sudah **dibuka**. Konfigurasi `config/migrations.php` memakai default `false`, dan `.env.example` berisi `MIGRATIONS_FROZEN=false`. Freeze sekarang hanya emergency switch.

Mekanisme pengaman freeze tetap tersedia melalui `app/Console/PreventFrozenMigrations.php` dan wiring di `app/Providers/AppServiceProvider.php`. Saat diaktifkan kembali, perintah schema-changing seperti migrate/rollback/fresh/refresh/reset/db:wipe/schema:dump diblokir, sedangkan pemeriksaan status tetap diperbolehkan. Rekonsiliasi ledger historis tersedia melalui `database:reconcile-migration-ledger`, tetapi command itu memang hanya boleh dipakai saat freeze aktif.

Migrasi status yang sudah dijalankan:

1. `2026_09_10_120000_consolidate_regular_lop_statuses` — batch 32.
2. `2026_09_10_121000_drop_legacy_project_status_columns` — batch 33.

Migrasi pertama menambah dan melakukan backfill status SDI/Golive ke LOP. Migrasi kedua melakukan rekonsiliasi terakhir, membatalkan proses bila ada project tanpa LOP, kemudian menghapus kolom parent legacy. `down()` tersedia untuk merekonstruksi kolom parent dari agregasi LOP bila rollback darurat diperlukan.

Hasil verifikasi database setelah migrasi:

| Pemeriksaan | Hasil |
|---|---:|
| Project reguler | 1.308 |
| LOP reguler | 1.360 |
| Project reguler tanpa LOP | 0 |
| Project PT2 | 7 |
| LOP PT2 | 297 |
| Project PT2 tanpa LOP | 0 |
| LOP reguler Golive / SDI approved | 2 / 2 |
| LOP PT2 Golive / SDI approved | 2 / 2 |

Sebaran `lops.status_progress` saat verifikasi:

- `persiapan_instalasi`: 1.344
- `finishing`: 6
- `instalasi`: 5
- `golive`: 2
- `inisiasi`: 1
- `survey`: 1
- `drop`: 1

Baseline `database/schema/mysql-schema.sql` sudah diperbarui dan memuat ledger kedua migrasi tersebut.

**Dua migrasi lama masih sengaja pending dan tidak boleh dijalankan otomatis tanpa audit terpisah:**

- `2026_08_31_120000_add_performance_indexes_for_pm_dashboard`
- `2026_09_07_090000_drop_role_enum_from_users_table`

### 0.5 Dashboard, filter, program, dan import yang sudah diselaraskan

- Dashboard Admin, Super TIF, dan PM membaca status/flag dari LOP.
- Filter status Dashboard Admin/Super TIF mengambil seluruh tahap aktif dari `project_stages`; DRM tidak muncul.
- Matriks ringkas tetap memakai tiga bucket visual agar desain tidak berubah:
  - Prepare: tahap awal sampai Persiapan Instalasi;
  - Progress: Instalasi dan Pengukuran;
  - Finish: Finishing, FI-OGP Golive, dan Golive.
- Bucket PT2 juga membaca tahap LOP: Preparation/Survey → Prepare; Progress/Instalasi → Progress; Finish/Dismantle/Mancore/Complete/Golive → Finish.
- Detail matriks dan filter aktif menggunakan `status_progress`, bukan status parent.
- Program OSP, NODE B, HEM, OLO, dan Konstruksi Eksternal sudah menggunakan filter status LOP.
- Data PID dan Data BOQ, modal edit, ekspor Excel, template CSV, `PidImportService`, serta job import lama sudah memakai `status_progress`.
- Template PID sekarang menggunakan header `status_progress` dengan contoh awal `inisiasi`.
- Import PID reguler mewajibkan `pid_sap` dan `nama_lop`; `id_ihld` hanya wajib untuk PT2 karena satu PID PT2 dapat mempunyai banyak LOP. Kolom lain tetap opsional dan nilai kosong tidak menimpa data existing.
- Import PID lama dan import CSV Project tidak lagi menulis `status_project`. Jalur CSV Project yang sebelumnya memiliki variabel validasi tidak aktif juga sudah diperbaiki agar membuat Project dan LOP secara transaksional, lalu menyimpan status ke LOP.
- Filter/daftar PT2 memakai `pt2_lops.status_progress`.
- Reminder project stale berhenti berdasarkan status LOP Golive/Drop.
- Bug pada bulk review evidence yang sebelumnya menyiapkan `$status` tetapi mencoba menulis variabel `$statusProject` yang tidak ada sudah diperbaiki; bulk review sekarang hanya mengubah status evidence sesuai domainnya.

File inti perubahan status:

- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/DashboardPmController.php`
- `app/Http/Controllers/ProgramController.php`
- `app/Http/Controllers/ImportController.php`
- `app/Http/Controllers/ProjectController.php`
- `app/Http/Controllers/AdminPt2Controller.php`
- `app/Http/Controllers/SdiController.php`
- `app/Services/Imports/PidImportService.php`
- `app/Jobs/ImportPidJob.php`
- `app/Console/Commands/PublishStaleProjectReminders.php`
- `app/Models/Lop.php`
- `app/Models/Project.php`

### 0.6 Verifikasi yang sudah dilakukan

- Lint seluruh file PHP yang berubah: **lulus**.
- Kompilasi seluruh Blade (`artisan view:cache`): **lulus**.
- Test fokus: **24 test / 94 assertion lulus**.
- Test mencakup baseline schema, rekonsiliasi ledger, migration freeze guard, service Finalisasi Survey, serta workflow Survey.
- Smoke test render penuh berhasil untuk:
  - Dashboard Admin dengan filter Survey;
  - Dashboard PM;
  - Data PID PT2 dengan filter Preparation;
  - Program OSP dengan filter Survey.

Suite bawaan penuh masih memiliki kegagalan lama yang tidak berasal dari refactor ini:

- test auth/profile memakai SQLite memory dan migration lama langsung mengubah tabel `evidences` yang belum dibuat;
- test contoh root mengharapkan HTTP 200, sedangkan aplikasi memang redirect ke login/dashboard (HTTP 302).

Jangan menganggap kegagalan tersebut sebagai regresi status tanpa memperbaiki fixture/baseline test SQLite terlebih dahulu.

### 0.7 Pekerjaan yang masih pending / tindakan Claude berikutnya

1. Jangan jalankan seluruh migration pending sekaligus. Audit dua migrasi lama yang disebut di 0.4 satu per satu.
2. Lakukan pengujian manual browser untuk Survey → Redesign → simpan desain → Sesuai → Simpan Draf → Selesai Survey → Perizinan menggunakan akun role nyata.
3. Setelah deploy perubahan import/job, restart queue worker agar worker lama tidak memakai kode yang masih menulis status parent.
4. Jika akan membersihkan master DRM secara fisik, audit dulu seluruh foreign key dan histori; kondisi saat ini sengaja mempertahankan row DRM.
5. Perbaikan suite SQLite/auth dapat dibuat sebagai pekerjaan terpisah; jangan mengubah alur produksi hanya untuk membuat test contoh bawaan lolos.
6. Working tree berisi rangkaian perubahan refactor ini dan belum boleh dibersihkan/reset secara destruktif. Periksa `git diff` sebelum mengedit area yang sama.

### 0.8 Aturan lanjutan agar tidak terjadi regresi

- Jangan membaca/menulis status pekerjaan dari tabel `projects` atau `pt2_projects`.
- Jangan menambah kembali `status_project` ke form, filter, export, atau model sebagai field penyimpanan.
- Setiap LOP pada project multi-LOP harus diperlakukan independen.
- Gunakan `ProjectStage::active()`/`sequential()` untuk opsi tahap reguler agar DRM tetap tersembunyi.
- Pertahankan alias input `status_project` hanya selama kompatibilitas template lama masih diperlukan; alias boleh dihapus setelah seluruh klien memakai `status_progress`.
- Jangan menghapus `status_progress_before_hold`, `mapping_status`, atau status milik domain lain.
- Pertahankan histori map/desain Survey; perubahan desain terbaru tidak boleh menghapus versi sebelumnya.
- Gunakan migration path spesifik untuk perubahan schema sensitif dan lakukan verifikasi jumlah data/orphan sebelum drop kolom.

---

## A. Upload PID/LOP — "wajib cuma PID + Nama LOP"

Saya baca langsung file yang menjalankan import PID sekarang: `app/Services/Imports/PidImportService.php` (dipanggil oleh `ProcessPidImportJob`, dipicu dari `ImportController`).

**Kondisi sekarang**: ada 3 header yang wajib ada di file upload — `pid_sap`, `id_ihld`, `nama_lop` (`PidImportService.php:120`), dan divalidasi ketat per baris di `parseRow()` (`:551-564`) — kalau salah satu kosong, baris itu ditolak (masuk daftar error, tidak diimport). Kolom lain (`branch`, `sto`, `mitra_name`, `tematik`, `batch`, `no_sp`, `tgl_sp`, `tgl_toc`, `execution_type`, `status_project`, dll) **sudah opsional** di importer ini — kalau kosong, baris tetap masuk dan kolom itu cuma dilewati (`nonBlankPayload()`). Jadi sebagian besar permintaanmu ("kolom lain opsional") sebenarnya sudah terpenuhi di jalur import ini; yang perlu benar-benar diubah adalah bagian **ID IHLD wajib**.

**Satu hal penting yang perlu kamu pastikan dulu**: kolom yang divalidasi wajib sekarang itu **`pid_sap`**, bukan `pid`. Di skema, `projects` punya dua kolom terpisah — `pid` (opsional, saat ini) dan `pid_sap` (yang justru dipakai sebagai kunci pencocokan "1 PID = 1 LOP" untuk project reguler — lihat `parseRow():566-570`, `persistChunk():339`). Waktu kamu bilang "yang wajib PID", maksudnya kolom `pid` atau `pid_sap`? Ini penting karena logika deduplikasi baris (mana yang dianggap project yang sama saat diimport ulang) saat ini 100% bergantung ke `pid_sap`, bukan `pid`. Kalau ternyata yang wajib itu `pid` (bukan `pid_sap`), berarti logika pencarian project yang sudah ada juga perlu diubah, bukan cuma validasinya.

**id_ihld**: untuk project reguler (bukan PT2), `id_ihld` cuma disimpan sebagai data (`lops.id_ihld`) — tidak dipakai sebagai kunci pencocokan sama sekali (kuncinya `pid_sap`). Jadi melonggarkan `id_ihld` jadi opsional **khusus untuk jalur reguler** itu perubahan yang aman dan kecil. Saya perlu pastikan tidak menyentuh jalur PT2 (di sana `id_ihld` memang dipakai sebagai bagian kunci karena 1 PID PT2 boleh banyak LOP).

**Dampak ke file lain yang perlu ikut disesuaikan (belum saya sentuh, cuma dipetakan)**:
- Template download `template_import_pid.csv` (`ImportController.php:2472`) — headernya perlu dicek ulang, kemungkinan perlu ditandai kolom mana wajib/opsional biar user upload tidak bingung.
- Form create/edit project manual (`ProjectController::store()/update()`, `DashboardController::storeProject()`) — saya belum cek validasi persisnya field-per-field; ini saya list sebagai PR (belum diverifikasi) karena belum baca detail validasi rule-nya.
- Halaman "Program ID" yang baru kita bangun untuk TIF/PM minggu ini — filter Region/Branch di sana dikelompokkan dari `lops.branch`. Kalau `branch` makin sering kosong (karena jadi opsional saat upload), baris-baris itu otomatis tidak masuk ke kelompok region manapun di filter itu — bukan bug baru, tapi konsekuensi nyata yang perlu kamu tahu begitu field ini dilonggarkan.
- Halaman edit detail LOP (tempat "nanti bisa diedit data LOP-nya" seperti yang kamu sebut) — saya belum audit form edit LOP secara spesifik; akan saya cek begitu detail refactornya kamu jelaskan, supaya validasinya konsisten dengan aturan wajib yang baru.

---

## B. Sub-step baru untuk Step 1 (Persiapan)

**Kondisi sekarang**: `lops.status_progress` di database cuma py py 3 nilai enum: `preparation`, `instalasi`, `finishing`. Tidak ada tempat menyimpan "lagi di sub-step mana" di dalam Persiapan. Progress% juga dihitung terpisah oleh `Project::progressSummary()` yang menganggap "persiapan selesai" itu cuma 1 syarat boolean (ada evidence `barang_tiba` DAN `perizinan` yang approved) — jauh lebih sederhana dari 5 sub-step yang kamu mau sekarang.

Ini berarti seluruh cara "persiapan" direpresentasikan di database maupun di logika progress **perlu dirombak total**, bukan ditambal. Sebelum saya eksekusi apapun, ada satu keputusan desain yang menurut saya perlu kamu putuskan dulu:

> Apakah 5 sub-step ini (inisiasi/survey/drm/perizinan/delivery) jadi **nilai baru di kolom `status_progress` yang sama** (jadi enum-nya nanti punya 7-8 nilai total: inisiasi, survey, drm, perizinan, delivery, instalasi, pengukuran, finishing), **atau** dibuat kolom terpisah (misal `persiapan_substep`) supaya `status_progress` yang sudah ada dan dibaca di banyak tempat (dashboard admin, PM, dsb — lihat audit sebelumnya) tidak berubah maknanya untuk instalasi/pengukuran/finishing yang sudah jalan?

Saya condong ke opsi kedua (kolom terpisah) supaya lebih aman terhadap kode lama yang sudah baca `status_progress`, tapi saya tahan dulu keputusannya sampai kamu jelaskan detail lanjutan — mungkin ada pertimbangan lain dari sisi kamu.

**Poin per sub-step (dari spek kamu, dicocokkan ke kode yang ada)**:

- **1.1 Inisiasi** — "PID dan BOQ sudah diupload dan match". Ini bisa nyambung ke infrastruktur import yang sudah ada (bagian A di atas + BOQ import biasa). Yang perlu dipertegas: definisi "match" itu presisinya seperti apa — otomatis (misal: semua item BOQ ketemu designator-nya, tidak ada baris gagal) atau perlu ada tombol konfirmasi manual dari admin/PM?

- **1.2 Survey & Input BOQ Survey** — trigger-nya "sudah di-assign ke waspang". Ini langsung bersinggungan dengan bug yang saya laporkan di audit sebelumnya: assign waspang/teknisi sekarang saling menghapus satu sama lain (`ProjectController::assignWaspang()`). Karena sekarang assign mau dijadikan pemicu resmi perpindahan status ke "survey", bug itu **jadi prasyarat yang harus dibenahi bersamaan**, bukan sekadar catatan audit lagi — kalau tidak, status "survey" bisa salah terpicu/ke-reset gara-gara bug itu.
  - "Waspang input BOQ material+quantity, atau upload file Excel sesuai templat Bulk Import BOQ" — saya sudah temukan templat itu: `ImportController::downloadBoqTemplate()` (baris 2486-2532), formatnya matrix (baris = designator, kolom = PID per project) yang dibaca ulang oleh `app/Services/Imports/BoqImportService.php`. Ini importer level ADMIN yang jalan sebagai queued job dan handle banyak PID sekaligus dalam 1 file. Untuk dipakai waspang (biasanya cuma untuk 1 project yang dia pegang), perlu saya cek apakah service ini bisa dipakai langsung apa adanya atau perlu jalur/endpoint sendiri yang lebih sederhana — belum saya putuskan.
  - Kendala survey: 30 pilihan flagging yang kamu kirim itu **belum ada tabel/mekanismenya**. Yang ada sekarang cuma `project_issues` (dipakai untuk fitur "lapor kendala" yang sudah jalan dari sisi waspang — tapi `issue_type` di sana cuma varchar bebas, TIDAK terhubung ke daftar pilihan tetap manapun). Kamu minta "buatkan tabel tersendiri" untuk flagging ini — saya catat sebagai tabel master baru (mis. daftar 30 kategori itu) + relasi dari record kendala ke tabel master itu, bukan menambah value ke `project_issues.issue_type` yang bebas teks. Yang masih perlu saya pastikan: apakah record "kendala survey" ini numpang di tabel `project_issues` yang sudah ada (tinggal ditambah kolom FK ke tabel master baru), atau memang kamu mau tabel kendala yang benar-benar baru khusus untuk Persiapan ini (terpisah dari fitur kendala waspang yang sudah ada sekarang)?

- **1.3 Proses DRM** — upload hasil pdf/foto. Ini paling sederhana, tinggal ikut pola evidence upload yang sudah ada (`evidences` table + `WaspangController::uploadEvidence()`), asal `stage`/`evidence_type` sub-step ini didaftarkan.

- **1.4 Perizinan** — ini yang paling kompleks:
  - Pilih jenis perizinan (12 pilihan) → sama seperti kendala survey, ini perlu tabel master tersendiri + UI kelola (bisa tambah/reduce pilihan nanti tanpa ubah kode) — sama seperti yang kamu minta.
  - **Kronologi berkala** (input teks + tanggal, berkali-kali seiring waktu) — ini semacam timeline/riwayat. Tidak ada tabel yang persis cocok untuk ini sekarang. `project_activity_logs` yang sudah ada sifatnya log otomatis sistem (assign, approve, dst — teks bebas, tidak didesain untuk ditampilkan sebagai timeline milik user), jadi kemungkinan besar memang perlu tabel baru khusus "kronologi perizinan" (per LOP/project, banyak baris, masing-masing punya tanggal+teks). Saya belum putuskan detail strukturnya, tunggu penjelasanmu.
  - Tombol kendala lagi di sub-step ini — apakah pakai **daftar flagging yang sama** dengan kendala survey di 1.2 (30 pilihan itu), atau daftar yang berbeda khusus perizinan? Kamu belum sebutkan daftarnya untuk yang ini, jadi saya perlu konfirmasi sebelum bikin tabel masternya.
  - Kalau perizinan selesai → upload BA KP (pdf) + eviden foto — ini juga bisa ikut pola evidence upload biasa, tinggal ditandai sebagai penutup sub-step 1.4.

- **1.5 Material Delivery** — upload eviden foto + deskripsi opsional. Paling sederhana, sama seperti 1.3, tinggal ikut pola evidence upload yang sudah ada.

---

## C. Yang otomatis ikut terdampak dari perubahan di atas

- `Project::progressSummary()` — logika "persiapanDone" yang sekarang (2 evidence_type checklist) sudah tidak relevan lagi begitu Persiapan pecah jadi 5 sub-step; ini perlu ditulis ulang total, bukan revisi kecil. Perlu saya tanya juga: skema progress% barunya mau tetap checklist rata (misal sekarang 4 step jadi 8 step → tiap step 12,5%), atau ada pembobotan lain per sub-step?
- Notifikasi & activity log — kalau tiap sub-step baru ini juga mau memicu notifikasi/log seperti pola yang sudah ada (assign, approve, dst), itu perlu ditambahkan konsisten di setiap transisi baru — sekalian jadi kesempatan membenahi ketidakkonsistenan yang saya laporkan di audit sebelumnya (bulk-action yang tidak logging/notifikasi, dsb).
- Middleware role — karena sub-step baru ini jelas-jelas per-role (waspang yang isi BOQ survey & upload eviden, kemungkinan PM/admin yang review/approve di titik tertentu), ini juga saya anggap relevan untuk masuk paket refactor yang sama, mengingat sekarang tidak ada penegakan role di backend sama sekali (temuan audit sebelumnya).

---

## Keputusan yang sudah kamu konfirmasi (2026-09-07)

1. Pencocokan project pakai `pid_sap` (bukan `pid`) — tetap seperti sekarang.
2. Sub-step baru (inisiasi/survey/drm/perizinan/delivery) masuk sebagai **nilai baru di kolom `status_progress` yang sama** (bukan kolom terpisah) — dipilih supaya lebih simpel.
3. "PID dan BOQ match" (definisi 1.1 selesai) = saat upload BOQ, sistem otomatis mencocokkan by IHLD atau Nama LOP yang diturunkan dari PID.
4. Kendala pakai **1 tabel master** untuk semua step (bukan beda tabel per step) — tiap step nantinya punya tombol kendala dengan pilihan dari master itu + teks kronologi + upload eviden, supaya setiap step tercatat sebagai satu timeline.
5. Record kendala tetap masuk ke tabel `project_issues` yang sudah ada (diperluas, bukan diganti) — supaya lebih mudah dibikin timeline-nya.
6. "Kronologi" bukan tabel/field terpisah — cukup teks deskripsi yang diketik di setiap record kendala/update, dan timeline-nya terbentuk otomatis dari urutan `created_at` record-record itu.
7. Bug assign waspang/teknisi saling menghapus → disetujui masuk paket refactor ini.
8. Middleware role backend → disetujui masuk paket refactor ini juga.

Berdasarkan keputusan di atas, saya audit lebih dalam 4 area yang langsung kena dampak. Semua ini masih murni audit — belum ada file yang saya ubah.

---

## D. Semua titik yang baca/tulis `lops.status_progress` (dampak dari keputusan #2)

Karena nilai enum-nya akan bertambah dari 3 (`preparation`/`instalasi`/`finishing`) jadi sekitar 8 (5 sub-step persiapan baru + instalasi/pengukuran/finishing), saya grep tuntas semua pemakainya:

**Yang MENULIS ke `status_progress`** (cuma ada 3 titik aktif + 1 kode mati):
- `ProjectController::approveEvidence()` (baris 710/715/720) — satu-satunya state-machine transition yang sungguhan jalan sekarang. Ini nanti jadi titik utama yang perlu dirombak total begitu ada 5 sub-step baru.
- `PidImportService.php:519` dan `ImportController::updatePid():845` — cuma set default `'preparation'` saat LOP baru dibuat. Perlu diganti ke default sub-step pertama (inisiasi) begitu enum berubah.
- `ImportPidJob.php:227` — kode mati (tidak pernah dipanggil), aman diabaikan.
- **Titik bocor yang perlu ikut dibenahi**: `ProjectController::bulkApprove()` mass-update `evidences.status` tapi **tidak pernah menyentuh `status_progress`** — kalau tidak dibenahi bareng, evidence yang di-bulk-approve tidak akan memajukan status LOP-nya sama sekali.

**Yang MEMBACA dengan exact-match filter** (ini yang paling rawan — begitu `preparation` hilang diganti 5 nilai baru, filter ini bisa diam-diam berhenti nemuin data):
- `DashboardController.php:403,707,1687` (filter status di dashboard admin, salah satunya di route yang ternyata sudah rusak duluan — lihat bagian F)
- `DashboardPmController.php:43-47` (widget ringkasan status di dashboard PM)

**Yang MEMBACA lewat bucket/matrix** (lebih aman — nilai baru otomatis masuk kelompok "preparation" secara default, tidak error, tapi granularitas 5 sub-step-nya hilang/tergabung jadi satu sampai kodenya diperbarui):
- `DashboardController.php` (matrix "Regular" di dashboard admin & super_tif) dan `DashboardPmController.php` (matrix dashboard PM) — dipakai di `admin/dashboard.blade.php`, `super_tif/dashboard.blade.php`, `pm/dashboard.blade.php`.

**Tidak ada satupun blade view yang baca `lops.status_progress` secara langsung** — semuanya lewat array matrix yang sudah dirakit controller. Jadi begitu saya ubah pemetaan nilai barunya, itu cukup dilakukan di level controller, tidak perlu bongkar banyak file blade untuk bagian ini.

---

## E. Alur `project_issues` sekarang (dampak dari keputusan #4/#5)

Ketemu **bug data-loss yang sedang berjalan** yang jadi lebih penting untuk dibenahi bareng refactor ini: `WaspangController::storeIssue()` menyimpan foto eviden kendala dengan key `'photo_paths'` — tapi `ProjectIssue::$fillable` **tidak mengizinkan kolom itu sama sekali** (baik `photo_path` maupun `photo_paths`). Karena Laravel diam-diam menolak field yang tidak ada di `$fillable`, path foto yang sudah ter-upload ke storage **tidak pernah benar-benar tersimpan di tabel `project_issues`** — satu-satunya alasan foto itu masih kelihatan sekarang adalah karena ada salinannya yang nyasar ke `project_activity_logs.meta` (JSON log generik), bukan dari `project_issues` itu sendiri. Ini persis kolom yang perlu saya perbaiki begitu saya tambahkan FK ke tabel master kendala yang baru — jadi bisa dibenahi dalam satu paket perubahan.

Juga ketemu: pilihan `issue_type` yang sekarang **kelihatannya** seperti dropdown tetap (form-nya di `waspang/inbox.blade.php` memang pakai `<select>` dengan 6 pilihan hardcode: perizinan/material/akses_lokasi/cuaca/teknis/lainnya), tapi itu cuma tampilan — di backend validasinya masih `string bebas`, jadi tidak benar-benar terhubung ke daftar manapun. Ini pas sekali dengan rencana kamu bikin tabel master kendala yang sungguhan.

Tidak ada halaman admin/PM untuk resolve kendala dengan catatan sendiri — yang ada baru "Resume Project" dari sisi waspang (auto-fill catatan resolusi). Jadi kalau kamu mau ada tahap admin/PM ikut menutup kendala, itu UI baru yang perlu dibuat, bukan tinggal pakai yang sudah ada.

Timeline kendala berbasis `project_issues` **belum ada** — yang ada sekarang cuma tampilan 1 kendala terbaru per project (bukan riwayat). Tapi ada halaman `admin/projects/tracking.blade.php` yang sudah punya pola tampilan timeline (kartu bertanggal, dikelompokkan per tipe aktivitas, termasuk cara menampilkan foto per entri) — sumbernya `project_activity_logs`, bukan `project_issues`, tapi pola tampilannya bisa langsung dipakai ulang untuk timeline `project_issues` yang baru.

---

## F. Pencocokan BOQ↔LOP (dampak dari keputusan #3) — kabar baik

Ternyata mesin pencocokan by IHLD/Nama LOP **sudah ada dan sudah jadi satu-satunya mode yang aktif** — `BoqImportService::resolveLop()` sudah mendukung mapping by `id_ihld` maupun `lop_name`, dan halaman upload BOQ (`ImportController::importBoq()`) memang cuma mengizinkan dua mode itu (ada komentar eksplisit di kode: "Mapping PID sengaja tidak dibuka pada UI final"). Jadi permintaanmu di poin 3 itu secara logika pencocokan sebenarnya **sudah kompatibel** dengan kode yang ada, tidak perlu dibangun dari nol.

Yang masih kurang: sekarang admin harus **tahu dan ketik manual** nilai IHLD/nama LOP di header kolom Excel-nya — belum ada langkah "kasih PID, sistem otomatis cari tahu IHLD/nama LOP-nya sendiri". Ini bagian yang perlu ditambahkan supaya sesuai dengan "diturunkan dari PID" yang kamu maksud.

Juga tidak ada satupun kode sekarang yang menandai "Inisiasi selesai" setelah BOQ berhasil di-match — proses import BOQ saat ini cuma update proses importnya sendiri + bikin baris BOQ, tidak menyentuh status LOP sama sekali. Ini titik yang perlu ditambahkan begitu status "inisiasi" resmi ada.

---

## G. Bug tambahan yang ketemu saat audit ini (di luar topik utama, tapi ketemu di file yang sama)

Beberapa hal ini saya temukan sambil audit area D-F di atas — saya laporkan supaya kamu bisa putuskan mau dibenahi sekalian atau nanti terpisah:

1. **Halaman upload BOQ (`admin.import.boq`) sekarang rusak** — controller-nya (`ImportController::boqIndex()`) merujuk ke view Blade yang tidak ada filenya sama sekali. Ini halaman yang dituju dari link aktif di `data-boq.blade.php`, jadi kalau ada yang klik, langsung error.
2. **Template download BOQ menyesatkan** — file `template_import_boq.xlsx` yang bisa didownload mencantumkan header kolom `PID_SAP_001/002/003`, padahal sistem sekarang cuma menerima pencocokan by IHLD/nama LOP (bukan PID). Siapapun yang isi template itu apa adanya, hasilnya tidak akan match.
3. Route `admin.dashboard.rekap_progress` juga rujuk ke view yang tidak ada (beda dari `pm/rekap_progress.blade.php` yang sudah kita kerjakan dan memang jalan).

Poin 1 dan 2 ini relevan banget karena langsung menyentuh alur upload BOQ yang mau dipakai untuk sub-step 1.1/1.2 — kalau tidak dibenahi, fitur baru yang kita bangun di atasnya otomatis ikut rusak.

---

## Sisa pertanyaan terbuka (jauh lebih sedikit sekarang)

1. ~~Untuk keputusan #3 (BOQ auto-match dari PID)~~ — **dijawab**: tetap pilih mode IHLD/nama LOP secara manual seperti sekarang.
2. ~~Poin G (halaman upload BOQ rusak + template menyesatkan)~~ — **dijawab**: sekalian dibenahi.
3. ~~Tabel master kendala~~ — **dijawab**: daftar sementara, bisa ditambah admin lewat UI kelola master.

---

## H. Flow end-to-end lengkap (dari penjelasan kamu 2026-09-07) + 2 pertanyaan desain

Total tahapan sekarang (dari penjelasan lengkapmu):

- **Step 1 — Persiapan**: 1.1 Inisiasi, 1.2 Survey, 1.3 DRM, 1.4 Perizinan, 1.5 Material Delivery.
- **Step 2 — Persiapan Instalasi** (ini persis logika "persiapan" yang lama sebelum refactor ini — cuma ganti nama, saya konfirmasi paham): upload eviden material barang tiba + upload eviden perizinan.
- **Step 3 — Instalasi** (existing): upload eviden foto sesuai list item designator.
- **Step 4 — Finishing** (existing) → begitu selesai, status jadi **FI OGP Golive**: ADMIN upload 4 hal (capture valins, PDF ABD & Valid4, KML, Mancore) → kalau syarat lengkap, role **SDI** verifikasi + upload capture UIM + toggle status jadi **Golive**.

Jadi totalnya ada 10 tahapan status (5 + 2 + 1 + 1 + 1 + 1), lintas 3 role berbeda (waspang untuk step 1-3, admin untuk FI OGP Golive, SDI untuk verifikasi Golive) dengan syarat kelengkapan yang beda-beda bentuknya di tiap tahap (upload 1 foto, upload per-item BOQ, upload 4 file spesifik, aksi verifikasi+toggle). Ini jauh lebih kompleks dari saat kamu putuskan "taruh di kolom yang sama biar simpel" (waktu itu masih dibayangkan 5 tahap tambahan saja). Dua pertanyaanmu:

### Menurutku soal tabel tersendiri

Saya sarankan pendekatan **hybrid**, bukan murni salah satu:

- **`lops.status_progress`** tetap dipertahankan sebagai kolom "posisi saat ini" yang simpel — ini tetap berguna untuk filter cepat dan tidak mengubah cara dashboard yang sudah ada bekerja. Jangan dibuang.
- Tapi saya sarankan tambah **1 tabel riwayat baru** (misal `lop_stage_histories`: lop_id, step, entered_at, completed_at, completed_by, catatan) yang mencatat kapan tiap tahap dimulai/selesai dan siapa yang menyelesaikannya. Alasannya konkret: begitu ada 10 tahap lintas 3 role dengan syarat sendiri-sendiri, kolom tunggal cuma menyimpan "sedang di posisi mana sekarang" — begitu pindah ke tahap berikutnya, jejak kapan/siapa yang menyelesaikan tahap sebelumnya hilang begitu saja (kecuali digali manual dari `project_activity_logs` yang formatnya teks bebas, tidak terstruktur per-tahap). Untuk tools monitoring project kayak ini, riwayat per-tahap itu sebenarnya salah satu data paling berharga (berapa lama macet di suatu tahap, siapa yang biasanya telat approve, dst) — sayang kalau tidak direkam terstruktur padahal effort tambahannya kecil.
- Untuk **FI OGP Golive** dan **Golive**, saya sarankan itu memang tidak cukup jadi 1 nilai status doang — datanya beda bentuk (4 path file dari admin, 1 path file + status verifikasi dari SDI). Ini pas ditaruh di 1-2 tabel kecil terpisah (bukan kolom tambahan di `lops` yang jadi bengkak), sesuatu seperti `lop_golive_submissions` (capture_valins_path, abd_valid4_path, kml_path, mancore_ref, submitted_by, submitted_at) + `lop_golive_verifications` (capture_uim_path, verified_by, verified_at). Setelah kedua tabel itu lengkap, barulah `status_progress` di-set jadi `golive`.

Satu temuan yang lumayan pas: untuk kebutuhan "upload Mancore" di FI OGP Golive, ternyata **sudah ada tabel `pt2_mancores`** (kolom `odp_label`, `odc_label`, `distribusi_core`, `feeder_core`, FK ke `project_id` — bukan ke PT2!) yang dari audit besar sebelumnya saya temukan sebagai tabel mati/tidak dipakai kode manapun untuk project reguler. Bentuknya persis cocok untuk kebutuhan Mancore di Golive reguler ini — kemungkinan besar itu memang sisa percobaan lama untuk fitur ini yang belum sempat diselesaikan. Bisa kita hidupkan lagi (dibersihkan relasinya) daripada bikin tabel baru dari nol. Untuk KML, `projects.kml_file`/`kml_lat`/`kml_lng` juga sudah ada — kemungkinan tinggal dipakai ulang.

Ringkasnya: saya tidak menyarankan bikin SATU tabel besar buat gantiin `status_progress` sepenuhnya — saya sarankan tetap simpel di kolom itu untuk "posisi sekarang", plus 2-3 tabel kecil pendukung untuk hal yang memang butuh data lebih dari sekadar label status. Kalau kamu tetap mau semuanya di 1 kolom saja demi kesederhanaan, saya bisa jalankan itu juga — cuma saya mau kamu sadar konsekuensinya (riwayat & data pendukung FI OGP Golive/Golive tidak akan tercatat rapi tanpa tabel tambahan).

### Menurutku soal desain stepper waspang

Saya sudah baca `resources/views/waspang/partials/stepper.blade.php` yang jalan sekarang — bentuknya `grid-cols-4` (4 lingkaran bernomor + garis penghubung, gaya mobile card di header biru), 1 lingkaran per Step besar (Persiapan/Instalasi/Pengukuran/Finishing), locked/unlocked tergantung step sebelumnya selesai atau belum.

Kabar baiknya: kalau dipetakan ke flow barumu, **jumlah step BESAR waspang sebenarnya tidak nambah banyak** — tetap sekitar 4 (Persiapan, Persiapan Instalasi, Instalasi, Finishing; FI OGP Golive & Golive itu bukan tugas waspang, jadi tidak perlu muncul di stepper waspang sama sekali). Yang nambah cuma jumlah **sub-step di DALAM** Step 1 (jadi 5) dan Step 2 (jadi 2, sebenarnya sama seperti sekarang).

Jadi saran saya: **jangan bikin 1 stepper horizontal super panjang** yang menampilkan semua 8-10 sub-tahap sekaligus (bakal penuh sesak di layar HP). Sebaiknya dua lapis:
- **Stepper atas tetap seperti sekarang** — 4 lingkaran besar (Persiapan / Persiapan Instalasi / Instalasi / Finishing), tidak berubah bentuk dari yang sudah jalan, jadi waspang yang sudah terbiasa tidak bingung.
- Di **dalam halaman Step 1 (Persiapan)** saja, tambahkan indikator kecil kedua — bisa berbentuk checklist vertikal ringkas (5 baris: Inisiasi/Survey/DRM/Perizinan/Delivery, masing-masing dengan ikon centang/terkunci) atau segmented pill horizontal kecil di bawah judul halaman. Ini cuma tampil saat waspang lagi di Step 1, jadi tidak menambah lebar stepper utama sama sekali.

Saya belum putuskan bentuk visual persisnya (checklist vertikal vs pill horizontal) — begitu masuk fase desain UI saya akan buatkan draft dulu sebelum coding, supaya kamu bisa lihat dan koreksi sebelum saya pasang ke halaman sungguhan.

Silakan lanjutkan detailnya — saya tunggu sebelum mulai eksekusi.

---

## I. Ralat flow (2026-09-08): step Pengukuran ketinggalan kesebut — dan satu temuan audit penting soal step itu

Flow yang benar sekarang (7 step besar, step 1 punya 5 sub-step):

1. **Persiapan** (baru, 5 sub: Inisiasi/Survey/DRM/Perizinan/Material Delivery)
2. **Persiapan Instalasi** (existing — ini yang tadinya bernama "Persiapan" sebelum refactor: upload eviden barang tiba + upload eviden perizinan)
3. **Instalasi** (existing)
4. **Pengukuran** (existing)
5. **Finishing** (existing)
6. **FI-OGP Golive** (baru, role admin)
7. **Golive** (baru, role SDI)

Catatan penting saya sendiri: di jawaban saya sebelumnya, saya juga sempat melewatkan Pengukuran waktu menghitung ulang jumlah node stepper waspang (saya bilang "tetap 4 node" — harusnya **5 node**: Persiapan / Persiapan Instalasi / Instalasi / Pengukuran / Finishing. FI-OGP Golive & Golive tetap tidak perlu muncul di stepper waspang karena itu tugas admin/SDI). Koreksi ini sudah saya catat.

**Temuan audit yang menurut saya penting untuk kamu tahu sebelum lanjut**: kamu bilang step Pengukuran itu "existing", dan secara UI/halaman memang benar sudah ada (`WaspangController::pengukuran()`, ada halaman upload eviden `otdr`/`opm`/`kedalaman` yang nyata). TAPI, saya cek langsung ke `Project::progressSummary()` (`app/Models/Project.php:257`):

```php
$pengukuranDone = $instalasiDone;
```

Progress "Pengukuran" saat ini **cuma nebeng/alias langsung ke status selesainya Instalasi** — tidak ada pengecekan independen apakah eviden `otdr`/`opm`/`kedalaman` benar-benar sudah diupload atau disetujui. Jadi walau halamannya nyata dan waspang bisa upload evidence di sana, secara sistem, upload itu **tidak berpengaruh apapun** ke progress/status project — begitu Instalasi selesai, Pengukuran otomatis ikut "selesai" tanpa syarat tambahan. Ini juga konsisten dengan kenapa `lops.status_progress` sekarang cuma punya 3 nilai enum (`preparation`/`instalasi`/`finishing`) — **tidak ada nilai "pengukuran" sama sekali** di database hari ini; itu murni tahap semu di level tampilan.

Karena kamu sekarang mau Pengukuran jadi salah satu nilai resmi di `status_progress` yang baru, ini pas jadi keputusan yang perlu kamu ambil:
- **Opsi A** — pertahankan perilaku sekarang (Pengukuran otomatis selesai begitu Instalasi selesai, cuma jadi label saja, upload otdr/opm/kedalaman tetap ada tapi sifatnya dokumentasi opsional).
- **Opsi B** — jadikan Pengukuran benar-benar step tersendiri yang mensyaratkan otdr+opm+kedalaman ter-upload (dan mungkin di-approve) dulu sebelum bisa lanjut ke Finishing — ini memperbaiki gap yang sudah ada, tapi berarti ada kemungkinan project yang sekarang "sudah Finishing" di datamu ternyata belum pernah benar-benar mengisi data pengukurannya.

Saya condong ke Opsi B karena lebih konsisten dengan filosofi tiap step yang lain (semuanya mensyaratkan bukti nyata), tapi saya tahan dulu sampai kamu konfirmasi — terutama karena Opsi B bisa berdampak ke status project yang sudah berjalan sekarang.

Dengan koreksi ini, total nilai `status_progress` yang perlu ditampung jadi 11: `inisiasi, survey, drm, perizinan, material_delivery, persiapan_instalasi, instalasi, pengukuran, finishing, fi_ogp_golive, golive`. Ini memperkuat rekomendasi tabel riwayat tambahan di bagian H — dengan 11 nilai lintas 4 role (waspang, admin, SDI, plus siapa saja yang approve di antaranya), jejak "kapan pindah ke tahap apa" makin penting untuk direkam terstruktur, bukan cuma disimpan sebagai 1 label kolom.

Silakan lanjutkan detailnya.

---

## J. Detail lanjutan (2026-09-08) — masih audit, belum eksekusi

### J.1 Upload PID — kolom `lops` yang belum tercakup importer sekarang

Saya cek ulang payload persis yang ditulis `PidImportService::persistChunk()` (baris 484-497) untuk row LOP. Yang SUDAH tercakup: `id_ihld`, `lop_name`, `pid_sap`, `tematik`, `sto`, `branch`, `batch`, `no_sp`, `tgl_sp`, `tgl_toc`, `mitra_name`, `program_sap`, `mapping_status`.

Yang **BELUM sama sekali** diterima importer ini padahal kolomnya ada di `lops` dan ada di `$fillable` model (24 kolom): `tahun_order`, `start_tgl`, `wo_smile`, `nilai_material`, `nilai_jasa`, `nilai_total`, `odp_8`, `odp_16`, `total_port`, `plan_tiang`, `realisasi_tiang`, `plan_kabel`, `realisasi_kabel`, `plan_galian`, `real_galian`, `nama_waspang`, `nik_waspang`, `nama_admin`, `nik_admin`, `est_prep`, `est_izin`, `est_delivery`, `est_instalasi`, `est_golive`, `package_id`.

Jadi "sesuaikan isi kolom dengan tabel lops lengkap" itu memang perubahan nyata — bukan cuma melonggarkan `id_ihld`, tapi menambah 24 kolom baru ke header template + parsing + payload, semuanya opsional (cuma `pid_sap` dan `lop_name` yang wajib, sesuai konfirmasimu). Saya sudah punya daftar persis kolom mana yang perlu ditambahkan, jadi begitu masuk fase eksekusi ini tinggal jalan, tidak perlu investigasi ulang.

### J.2 `status_progress` — jawaban saya soal tabel master

Setuju, dan menurut saya ini pilihan yang lebih baik dibanding rekomendasi saya sebelumnya (enum hardcode + tabel riwayat terpisah). Alasannya persis seperti yang kamu bilang: begitu nanti ada status baru lagi (seperti HOLD/DROP yang kamu minta sekarang), dengan ENUM itu berarti migration + ubah kode di banyak tempat; dengan tabel master, itu tinggal insert baris baru + admin atur urutan/warna/nama lewat UI — sama persis pola yang sudah kita sepakati untuk kendala dan kategori perizinan. Jadi 3 hal ini (status flow, kendala, kategori perizinan) semuanya konsisten jadi "master data yang bisa dikelola admin", bukan cuma status_progress sendirian.

Desain yang saya rencanakan: bikin tabel master (misal `project_stages`: id, code, label, urutan/`sequence`, `phase_group`, warna badge, keterangan, `is_active`). Kolom `lops.status_progress` sendiri saya sarankan **tetap berbentuk string code** (bukan diganti jadi `status_progress_id` angka) — supaya kode yang sudah ada sekarang (48 titik baca/tulis yang sudah saya petakan di bagian D) tidak perlu dibongkar semua jadi query JOIN; validasi "apakah code ini valid" cukup dicek ke tabel master saat disimpan. Jadi tabel master ini fungsinya sebagai "daftar nilai yang diizinkan + metadata tampilan", bukan mengubah cara kolom `status_progress` dipakai sehari-hari.

**Soal HOLD dan DROP** — ini saya perlu angkat sebagai catatan penting, karena sifatnya beda dari 11 tahap yang lain. 11 tahap itu berurutan (satu selesai baru lanjut ke berikutnya). HOLD dan DROP sepertinya bukan "tahap berikutnya" dalam urutan itu — HOLD kedengarannya "pause sementara, bisa terjadi di tahap manapun" dan DROP "batal permanen, juga bisa terjadi di tahap manapun". Kalau keduanya cuma ditambahkan sebagai 2 nilai lagi di `status_progress` yang sama, ada risiko: begitu sebuah LOP di-HOLD, sistem "lupa" itu tadinya lagi di tahap apa (karena kolomnya cuma bisa menyimpan 1 nilai) — begitu HOLD dicabut, LOP itu harus lanjut dari tahap terakhir sebelum di-hold, bukan mulai dari awal.

Saya sarankan: HOLD/DROP tetap masuk tabel master yang sama (biar tetap satu sumber kelola), tapi ditandai sebagai tipe khusus ("status jeda/berhenti", bukan "tahap alur"), dan saya tambahkan 1 kolom lagi di `lops` (misal `status_progress_before_hold`) yang otomatis menyimpan tahap terakhir sebelum di-HOLD, supaya begitu di-resume bisa kembali persis ke tahap yang benar. Untuk DROP saya asumsikan sifatnya final (tidak ada "resume" lagi) — kalau ternyata kamu mau DROP juga bisa dibatalkan/dikembalikan, tolong dikoreksi.

Apakah pemahaman saya soal HOLD/DROP ini benar (jeda vs final), atau ada maksud lain yang saya lewatkan?

### J.3 Kategori perizinan — tabel master

Sesuai dengan yang sudah saya rencanakan di analisa sebelumnya — 1 tabel master (`permit_categories` atau serupa: id, nama, urutan, is_active), dan dropdown pemilihannya muncul spesifik saat `status_progress` LOP sedang di tahap Perizinan. Tidak ada yang perlu diaudit tambahan di sini, desainnya sudah align dengan temuan saya sebelumnya.

### J.4 Pengukuran — Opsi B dikonfirmasi + radio "tidak ada"

Dengan tambahan radio "tidak ada pengukuran" per item, desainnya jadi: LOP baru bisa lanjut dari Pengukuran ke Finishing kalau **kelima** item ini masing-masing sudah "beres" — beres berarti salah satu dari (a) ada eviden ter-upload, atau (b) ditandai "tidak ada" oleh waspang:

1. OTDR
2. FILE SOR (ini item baru, belum ada `evidence_type` untuk ini di kode sekarang)
3. OPM
4. Kedalaman Galian (sudah ada, `evidence_type: kedalaman`)
5. Eviden Lainnya (baru, tampaknya semacam slot bebas untuk dokumentasi tambahan)

Catatan teknis: kolom `evidences.file_path` sekarang **NOT NULL** — jadi opsi "tidak ada" tidak bisa disimpan sebagai baris `evidences` biasa tanpa file (akan gagal insert). Saya sarankan bikin 1 tabel kecil terpisah untuk menyimpan status per-item pengukuran ini (misal `lop_measurement_checks`: lop_id, item_key, is_not_applicable, catatan, evidence_id nullable) — jadi baik "ada eviden" maupun "ditandai tidak ada" sama-sama punya 1 baris yang jelas per item, dan gampang dicek "kelima item sudah beres semua belum" untuk gate ke Finishing.

Satu hal yang perlu kamu putuskan juga: begitu Opsi B ini aktif, project-project yang **sekarang** statusnya sudah Finishing/lebih (padahal belum pernah isi data pengukuran sama sekali, karena selama ini pass-through otomatis) — apakah saya perlu jalankan proses "backfill" otomatis (tandai kelima item itu "tidak ada/legacy" untuk semua LOP yang statusnya sudah lewat Pengukuran saat ini), supaya data lama tidak keblokir aturan baru? Atau kamu mau cek manual dulu?

---

## K. Eksekusi Stage 1 (2026-09-08) — Migration + Model master data

Instruksi "lanjut eksekusi secara bertahap dan lakukan testing" sudah diterima. Berikut yang sudah dieksekusi dan disinkronkan ke server (belum di-migrate — menunggu kamu jalankan `php artisan migrate`).

### K.1 Migration baru (urutan eksekusi, 10 file)

1. `2026_09_08_090000_create_project_stages_table.php` — tabel master `project_stages` (id, code unik, label, phase_group, sequence, color, is_pause_type, is_terminal, is_active, description). Seed 13 baris: 11 tahap berurutan (`inisiasi` s/d `golive`, sequence 1-11) + `hold` (is_pause_type) + `drop` (is_terminal).
2. `2026_09_08_090100_create_kendala_categories_table.php` — tabel master `kendala_categories`, seed 32 item flagging kendala persis daftar yang kamu berikan.
3. `2026_09_08_090200_create_permit_categories_table.php` — tabel master `permit_categories`, seed 12 item jenis perizinan persis daftar yang kamu berikan.
4. `2026_09_08_090300_convert_lops_status_progress_to_project_stages.php` — mengubah `lops.status_progress` dari ENUM jadi VARCHAR(50) + FK ke `project_stages.code`; migrasi data lama `preparation` → `persiapan_instalasi` (asumsi saya, lihat catatan di bawah); tambah kolom `status_progress_before_hold` (untuk resume dari HOLD) dan `permit_category_id` (FK ke `permit_categories`).
5. `2026_09_08_090400_add_kendala_category_and_photos_to_project_issues_table.php` — tambah `kendala_category_id` (FK ke `kendala_categories`) dan `photo_paths` (JSON) ke `project_issues` yang sudah ada; kolom `issue_type` lama TIDAK dihapus (tetap kompatibel).
6. `2026_09_08_090500_create_lop_measurement_checks_table.php` — tabel baru untuk gating Pengukuran Opsi B (5 item: otdr/file_sor/opm/kedalaman/eviden_lainnya, masing-masing bisa "ada eviden" atau ditandai "tidak ada").
7. `2026_09_08_090600_create_lop_stage_histories_table.php` — tabel riwayat per-LOP kapan masuk/selesai tiap tahap (jejak timeline).
8. `2026_09_08_090700_create_lop_golive_submissions_table.php` — tabel upload admin di FI-OGP Golive (capture valins, PDF ABD & Valid4, KML, Mancore — Mancore cuma path file foto/excel, bukan tabel relasional terpisah).
9. `2026_09_08_090800_create_lop_golive_verifications_table.php` — tabel verifikasi SDI di Golive (capture UIM + siapa/kapan verifikasi).
10. `2026_09_08_090900_drop_pt2_mancores_and_pt2_surveys_tables.php` — drop tabel legacy `pt2_mancores` dan `pt2_surveys` sesuai konfirmasimu. Tabel PT2 asli (`mancores_pt2`, `surveys_pt2`) TIDAK disentuh.

### K.2 Model baru (7 file)

`ProjectStage`, `KendalaCategory`, `PermitCategory`, `LopMeasurementCheck` (dengan konstanta 5 item + helper `isDone()`), `LopStageHistory`, `LopGoliveSubmission` (dengan helper `isComplete()`), `LopGoliveVerification`.

### K.3 Model yang diedit (3 file)

- `Lop.php` — tambah `status_progress_before_hold`, `permit_category_id` ke fillable; tambah relasi `stage()`, `permitCategory()`, `measurementChecks()`, `stageHistories()`, `goliveSubmission()`, `goliveVerification()`.
- `ProjectIssue.php` — tambah `kendala_category_id`, `photo_path`, `photo_paths` ke fillable (sekalian perbaiki bug `photo_paths` yang sebelumnya tidak ada di fillable sama sekali sehingga hilang saat disimpan); tambah cast array untuk `photo_paths`; tambah relasi `project()`, `lop()`, `user()`, `kendalaCategory()` (sebelumnya nol relasi).
- `Project.php` — hapus relasi mati `pt2Survey()`/`pt2Mancore()` (sudah dicek, nol pemakaian di kode manapun), diganti komentar penjelasan.

Semua 10 migration + 10 model (baru & edit) sudah lolos `php -l` dan sudah disinkronkan ke server tanpa error (20/20 `written`, 0 `rejected`).

### K.4 Catatan penting sebelum migrate

- Migrasi data `preparation` → `persiapan_instalasi` di migration #4 adalah **asumsi saya** (LOP lama yang statusnya "preparation" dulu hanya melalui logic barang_tiba+perizinan, yang sekarang jadi Persiapan Instalasi — jadi saya anggap mereka sudah "lewat" 5 sub-step Persiapan yang baru). Kalau ternyata kamu mau perlakuan lain untuk data lama, beri tahu sebelum migrate, atau bisa disesuaikan lagi setelahnya.
- Kedua pertanyaan terbuka sudah terjawab:
  1. **HOLD** = jeda sementara, bisa resume. **DROP** = batal, TAPI bisa di-reset kembali di kemudian hari kalau LOP itu ternyata mau dilanjutkan lagi (bukan final permanen seperti asumsi awal saya). Karena itu, kolom `lops.status_progress_before_hold` sengaja saya desain dipakai bersama untuk resume dari HOLD **maupun** dari DROP (satu LOP cuma bisa berada di salah satu status jeda ini dalam satu waktu, jadi aman pakai 1 kolom). Logic tombol "resume dari HOLD/DROP" sendiri masuk pekerjaan Stage 2.
  2. **Pengukuran backfill**: dikonfirmasi backfill otomatis jadi "tidak ada/legacy" untuk LOP yang sekarang sudah Finishing/lebih. Ini akan saya eksekusi sebagai bagian dari migration `2026_09_08_090500_create_lop_measurement_checks_table.php` (begitu tabelnya dibuat, langsung diisi baris `is_not_applicable=true` + catatan "legacy/backfill otomatis" untuk kelima item, untuk semua LOP yang `status_progress`-nya sudah di tahap Finishing atau lebih) — supaya data lama tidak keblokir gate Pengukuran yang baru. Detail migrasinya akan saya tulis saat mengerjakan sisa Stage 1 setelah migration #4 ini lolos.

### K.4.1 FIX setelah percobaan migrate pertama gagal

Migration #4 (`2026_09_08_090300_convert_lops_status_progress_to_project_stages.php`) gagal di langkah pembuatan FK `lops_status_progress_foreign` dengan error `1005`/errno `150` ("Foreign key constraint is incorrectly formed").

**Penyebab**: tabel `lops` (dibuat manual lewat SQL, bukan migration) punya collation default `utf8mb4_general_ci`, sedangkan `project_stages` (dibuat lewat Laravel Schema Builder) ikut collation default koneksi `utf8mb4_unicode_ci` (dikonfirmasi dari `config/database.php`). Saya cek juga dump SQL asli kamu — memang ada campuran `utf8mb4_general_ci` dan `utf8mb4_unicode_ci` di berbagai tabel, konsisten dengan sebagian tabel dibuat manual dan sebagian lewat migration. MySQL/MariaDB menolak bikin FK antar kolom string dengan collation berbeda, walau tipe datanya sama-sama VARCHAR.

**Perbaikan** (sudah disinkronkan ke server): migration #4 sekarang membaca charset/collation `project_stages.code` yang sebenarnya lewat `INFORMATION_SCHEMA` lalu memaksa `lops.status_progress` dan `lops.status_progress_before_hold` memakai persis charset/collation yang sama — jadi tidak menebak-nebak nilai default. Migration ini juga sudah dibuat idempotent (pakai `Schema::hasColumn` dan cek `INFORMATION_SCHEMA.TABLE_CONSTRAINTS` sebelum menambah kolom/FK), supaya aman dijalankan ulang walau langkah 1-5 sempat berhasil sebelum gagal di langkah 6 pada percobaan pertama.

Catatan: FK integer lain (`lop_id`→`lops.id_lop`, `evidence_id`→`evidences.id_evidence`, `checked_by`/`completed_by`/`submitted_by`/`verified_by`→`users.id_user`, dst di migration #6-#9) sudah saya cek ulang ke dump SQL asli — semuanya `bigint(20) UNSIGNED`, sama persis dengan yang dihasilkan `unsignedBigInteger()` Laravel, jadi tidak ada risiko error serupa di situ.

### K.5 Cara testing Stage 1

Karena saya tidak bisa menjalankan artisan/mysql dari sisi saya (device bridge saya tidak punya akses ke PHP/MySQL kamu), tolong jalankan ini di terminal Mac kamu:

```
cd /Applications/XAMPP/xamppfiles/htdocs/dompis-cons
php artisan migrate
```

Karena migration #1-#3 (`project_stages`, `kendala_categories`, `permit_categories`) sudah berhasil di percobaan pertama, Laravel akan otomatis lanjut dari migration #4 yang gagal (file-nya sudah saya perbaiki) lalu terus ke #5-#10. Tidak perlu rollback atau hapus apa pun secara manual dulu.

Yang perlu dicek setelah itu:
1. Harus muncul migration #4 s/d #10 berhasil jalan, tidak ada error.
2. Cek tabel baru: `project_stages` harus berisi 13 baris, `kendala_categories` 32 baris, `permit_categories` 12 baris.
3. Cek `lops.status_progress` sudah jadi VARCHAR dengan FK ke `project_stages.code`, dan LOP yang tadinya `preparation` sekarang jadi `persiapan_instalasi`.
4. Pastikan tabel `pt2_mancores` dan `pt2_surveys` sudah tidak ada lagi, sementara `mancores_pt2` dan `surveys_pt2` masih ada seperti biasa.
5. Aplikasi masih bisa diakses normal (belum ada perubahan controller/view di stage ini, jadi seharusnya belum ada perubahan perilaku yang terlihat — ini murni perubahan skema).

Kalau semua lancar, beri kabar supaya saya lanjut ke Stage 2 (logic backend: importer PID, progressSummary, fix bug assign waspang/teknisi, middleware role).

Update: Stage 1 sudah dikonfirmasi berhasil migrate (setelah 2 kali fix masalah collation FK). Stage 2 sudah mulai dieksekusi -- lihat bagian L di bawah.

---

## L. Eksekusi Stage 2 (2026-09-08) — Backend logic (partial)

Bagian dari Stage 2 yang SUDAH selesai dan disinkronkan ke server:

### L.1 Fix bug assign waspang/teknisi

`ProjectController::assignWaspang()` sebelumnya SELALU meng-null-kan role yang tidak sedang di-assign (assign waspang otomatis menghapus teknisi yang sudah ada, dan sebaliknya) -- baris `'waspang_id' => null`/`'teknisi_id' => null` yang eksplisit di kode lama. Sekarang hanya kolom milik role yang sedang di-assign yang diisi; role yang lain (kalau sudah ada) tidak disentuh sama sekali.

### L.2 PidImportService.php -- 24 kolom lops + id_ihld opsional

- `id_ihld` sekarang HANYA wajib untuk import PT2 (dedup key PT2 tetap `pid_sap`+`id_ihld`). Untuk LOP reguler, cukup `pid_sap` + `nama_lop`.
- 24 kolom `lops` yang sebelumnya tidak tersentuh importer (tahun_order, start_tgl, wo_smile, nilai_material, nilai_jasa, nilai_total, odp_8, odp_16, total_port, plan_tiang, realisasi_tiang, plan_kabel, realisasi_kabel, plan_galian, real_galian, nama_waspang, nik_waspang, nama_admin, nik_admin, est_prep, est_izin, est_delivery, est_instalasi, est_golive, package_id) sekarang semuanya diterima sebagai kolom opsional -- kalau kosong/tidak valid, cukup jadi null (tidak memblokir baris), sesuai konfirmasimu.
- Kolom baru `package_code` (bukan kolom `lops`, murni bantuan) -- diresolve ke `package_id` lewat lookup ke tabel `packages` (case-insensitive); kalau kodenya tidak dikenali, `package_id` dibiarkan kosong (bisa diisi lewat edit UI nanti), TIDAK jadi error.
- LOP reguler baru sekarang mulai dari `status_progress = 'inisiasi'` (bukan lagi `'preparation'`); LOP PT2 tidak berubah (tetap `'preparation'`, refactor ini tidak menyentuh flow PT2).
- Template download (`ImportController::downloadPidTemplate()`) sudah diupdate mencantumkan semua kolom baru + contoh data, dengan catatan mana yang wajib.

### L.3 Middleware role-based backend + restrukturisasi routes/web.php

Ini bagian PALING BESAR & PALING BERISIKO di Stage 2 karena saya sama sekali tidak bisa live-test hasil akhirnya (device bridge saya tidak bisa jalankan artisan atau membuka browser ke aplikasimu). **Mohon benar-benar ditest tiap role setelah ini** -- lihat checklist testing di L.3.5.

**Temuan awal**: `routes/web.php` (37KB) ternyata satu file datar tanpa struktur role sama sekali -- cuma dibungkus `auth` middleware, jadi SIAPAPUN yang login (apapun rolenya) bisa membuka URL admin/waspang/PT2/dst kalau tahu URL-nya persis. Sebagian kecil sudah punya grouping prefix (pm/, teknisi/pt2/, admin/pt2/, sdi/, surveyor/, gis-cad/) tapi juga belum ada role check.

**Metodologi**: karena saya tidak bisa live-test, saya tidak menebak role tiap route -- saya gali ground truth dari 2 sumber yang SUDAH ada di kode:
1. Beberapa controller (DashboardController, GisCadController, SurveyorController, UserManagementController) ternyata SUDAH punya guard role manual sendiri (`ALLOWED_ROLES`, `in_array($role, [...])`, dst) -- ini saya pakai APA ADANYA sebagai kebenaran, tinggal disamakan di level middleware.
2. Untuk controller yang belum punya guard sama sekali, saya grep `resources/views/{role}/components/sidebar*.blade.php` tiap role untuk melihat route mana yang benar-benar dilink dari sidebar role itu -- dipakai sebagai bukti "role ini memang dimaksudkan mengakses route ini".

**Middleware baru**: `App\Http\Middleware\CheckRole` (alias `role`, didaftarkan di `bootstrap/app.php`) -- dipakai sebagai `->middleware('role:admin,superadmin,...')`. Kalau role user tidak ada di daftar, abort 403. Route `dashboard` SENGAJA TIDAK diberi role gate karena ini target redirect universal semua alur auth (login/register/verify-email/dst) -- `DashboardController::index()` sendiri yang meneruskan tiap role ke dashboard rolenya masing-masing.

**Pemetaan role per grup route** (di `routes/web.php`, urutan sesuai file):

| Grup route | Role diizinkan | Sumber bukti |
|---|---|---|
| Admin general (dashboard admin, projects CRUD, BOQ, assign-waspang, tracking, download preview/zip) | admin, superadmin, super_tif, officer | `DashboardController::index()` baris 52 (guard yang sudah ada) |
| Approval Eviden (`admin.evidences.*`) | admin, superadmin, super_tif (**officer di-exclude**) | Migration `seed_officer_role.php`: "kecuali ... Approval Eviden"; sidebar officer nol link ke `admin.evidences.*` |
| Waspang mobile (`waspang.*`) | waspang | `DashboardController::index()` HANYA redirect role waspang ke sini; nol sidebar role lain me-link `waspang.*` |
| Master Designator/Package/Price (`designators.*`, `packages.*`, `designator-prices.*`) | admin, superadmin, super_tif (**officer di-exclude**) | Migration seed_officer_role: "kecuali Master Designator"; sidebar officer nol link |
| Data PID & Data BOQ (`admin.data-pid`, `admin.data-pid.export`, `admin.data-boq`, `admin.data-boq.export`) | admin, superadmin, super_tif, officer | **FIX 2026-09-08**: awalnya digabung 1 gate dengan Bulk Import (officer di-exclude) -- SALAH, sidebar officer ternyata memang link ke `admin.data-pid`/`admin.data-boq` di bawah label "Master Data" (beda dari form upload `admin.import.pid`/`admin.import.boq` yang memang tetap di-exclude). Dipisah jadi role-gate sendiri. |
| User Management (`admin.users.*` umum) | superadmin, officer | `UserManagementController::ensureSuperAdminOrOfficer()` (guard yang sudah ada, persis) |
| User Management (reset-password, force-delete) | superadmin saja | `UserManagementController::ensureSuperAdmin()` (guard yang sudah ada) |
| Import PID & BOQ (`admin.import.*`) | admin, superadmin, super_tif (**officer di-exclude**) | Migration seed_officer_role: "kecuali ... Bulk Import Data"; sidebar officer nol link |
| Dashboard PM (`pm.*`) | pm, tif | `DashboardController::index()` HANYA redirect pm/tif ke sini; komentar asli "TIF memakai dashboard sama persis dengan PM" |
| Teknisi PT2 (`teknisi.pt2.*`) | teknisi | `DashboardController::index()` HANYA redirect teknisi ke sini |
| Approval PT2 admin (`admin.pt2.*`, termasuk BAUT/LACT) | admin, superadmin, super_tif, officer | Sidebar admin, officer, DAN super_tif sama-sama link ke `admin.pt2.*` (beda dengan Approval Eviden PT3 -- officer TIDAK di-exclude di sini) |
| SDI (`sdi.*` + route golive yang tadinya nyasar tanpa middleware) | sdi | `DashboardController::index()` HANYA redirect sdi ke sini |
| Survey Lapangan (`surveyor.*`) & GIS-to-CAD (`gis-cad.*`, `admin.gis-cad.*`, `admin.site-surveys.*`) | sdi_surveyor, admin, sdi, waspang, superadmin, super_tif, officer | `GisCadController`/`SurveyorController::ALLOWED_ROLES`. **FIX 2026-09-08**: `officer` awalnya tidak ikut (constant aslinya memang tidak menyebut officer), tapi sidebar officer ternyata link ke `admin.gis-cad.index`/`admin.site-surveys.index` -- constant-nya kelihatannya belum sempat diupdate saat role officer dibuat. Ditambahkan `officer` baik di middleware maupun di `ALLOWED_ROLES` constant kedua controller itu supaya konsisten. |
| Program (`program.*`: OSP/Node B/HEM/OLO/Konstruksi Eksternal) | pm, tif, admin, superadmin, super_tif, officer | Sidebar pm, admin, officer, DAN super_tif sama-sama link ke `program.*`; exclusion Konstruksi Eksternal untuk super_tif/tif SUDAH dijaga sendiri di `ProgramController` (tidak perlu diduplikasi) |
| Route PT2 yang tadinya nyasar tanpa middleware sama sekali (`admin.pt2.assign`, `admin.pt2.index`, dst di luar prefix group) | admin, superadmin, super_tif, officer | Disamakan dengan grup `admin.pt2.*` di atas (route name-nya memang satu keluarga) |

**3 celah keamanan nyata yang KETEMU & langsung diperbaiki sebagai bagian dari kerjaan ini** (bukan cuma "role belum dibatasi", tapi benar-benar TANPA middleware `auth` sama sekali -- bisa diakses tanpa login):
1. Seluruh grup `program.*` (halaman & export OSP/Node B/HEM/OLO/Konstruksi Eksternal) -- sebelumnya publik total.
2. 8 route PT2 di luar prefix group manapun (`/import/pt2/upload-pid`, `/import/pt2/upload-boq`, `/pt2-assignments/assign`, `/pt2-assignments/{id}/remove`, `/pt2`, `/pt2/tracking/{id}`, `/pt2/destroy-lop/{id}`, `/pt2/assignments/store`, `/pt2/assignments/remove/{id}`) -- sebelumnya publik total.
3. `POST /sdi/pt2/golive/{lop_id}` (nama route `sdi.eksekusi.golive`) -- sebelumnya publik total.
4. 4 route polling/download error import PID & BOQ (`admin.import.pid.status`, `admin.import.pid.errors.download`, `admin.import.boq.status`, `admin.import.boq.errors.download`) -- ternyata terdaftar DUA KALI di file lama: sekali di dalam grup `admin/import` yang ter-auth, sekali lagi di blok terpisah TANPA middleware apapun di akhir file. Blok duplikat itu (termasuk 2 route lain yang PERSIS sama -- `admin.import.pid`/`admin.import.pid.upload`/`admin.import.boq` GET yang di-declare ulang) sudah dihapus; ke-4 route unik sudah dipindah ke dalam grup `admin/import` yang sudah ter-auth+ter-role-gate.

**Hal lain yang KETEMU tapi SENGAJA TIDAK disentuh** (di luar scope permintaanmu, saya tidak mau menebak & mengubah tanpa konfirmasi): route `admin.import.lop`/`admin.import.lop.upload`/dst di dalam grup `Route::prefix('admin/import')` di-declare dengan path absolut `/admin/import/lop` (bukan relatif `/lop`) -- karena nested di dalam prefix `admin/import`, ini kemungkinan menghasilkan URL ganda `/admin/import/admin/import/lop`. Ini SUDAH ADA di file asli sebelum saya sentuh (saya cuma pindahkan grouping-nya, tidak mengubah path routenya), jadi saya biarkan apa adanya -- kalau memang ini bug, lebih aman diperbaiki terpisah setelah dikonfirmasi (mungkin memang sengaja begitu & sudah ada workaround di tempat lain).

Juga ada 1 route name yang tadinya didaftarkan 2x dengan URI BEDA (`admin.import.boq.upload` sempat menunjuk baik ke `/admin/import/boq/upload` maupun `/admin/import/boq`) -- saya pertahankan KEDUANYA tetap bisa diakses (supaya tidak ada fungsi yang hilang), tapi URI kedua sekarang punya nama route terpisah `admin.import.boq.upload.alt` supaya tidak lagi ambigu.

### L.3.5 Checklist testing WAJIB untuk middleware ini

Karena saya tidak bisa live-test, tolong login bergantian dengan akun tiap role (atau ganti role satu user test) dan pastikan:

1. Semua role masih bisa login & mendarat di dashboard masing-masing seperti biasa (waspang -> waspang.dashboard, pm/tif -> pm.dashboard, teknisi -> teknisi.pt2.index, sdi -> sdi.index, sdi_surveyor -> surveyor.index, admin/superadmin/super_tif/officer -> dashboard admin).
2. Role **officer**: pastikan memang TIDAK bisa buka Master Designator/Package/Price, Bulk Import (PID/BOQ), dan Approval Eviden (harus dapat halaman 403) -- tapi BISA buka User Management, Approval PT2, dan sisanya seperti admin.
3. Role **admin/superadmin/super_tif**: pastikan semuanya tetap normal seperti biasa (dashboard, projects, evidence approval, master data, import, PT2, program). superadmin tambahan bisa buka User Management; admin/super_tif TIDAK bisa (harus 403).
4. Role **pm/tif**: dashboard PM & menu Program (OSP/Node B/HEM/OLO) tetap normal; tif tetap TIDAK bisa buka Konstruksi Eksternal (403, ini business-logic lama, bukan yang baru saya tambahkan).
5. Role **waspang**: semua halaman `waspang.*` (persiapan/instalasi/pengukuran/finishing, upload eviden, kendala) tetap normal.
6. Role **teknisi**: seluruh alur `teknisi/pt2/*` tetap normal.
7. Role **sdi**: dashboard SDI, golive PT2, admin.site-surveys tetap normal.
8. Kalau ada role yang tiba-tiba dapat 403 di halaman yang SEHARUSNYA boleh dia akses, itu tandanya pemetaan role saya di atas salah untuk grup itu -- kabari saya persis di halaman/menu mana, saya perbaiki cepat (cuma tinggal ubah daftar role di 1 baris `->middleware('role:...')`, tidak perlu ubah struktur lain).

**Update hasil testing (2026-09-08)**: kamu sudah test semua role login & jalankan menu -- SEMUA AMAN kecuali role **officer** sempat tidak bisa buka Data PID & Data BOQ. Sudah diperbaiki (lihat baris "Data PID & Data BOQ" di tabel atas) dan disinkronkan. Sekaligus saya audit ulang seluruh link sidebar officer satu-per-satu untuk cari kemungkinan gap serupa yang belum sempat ketemu saat testing -- ketemu 1 lagi: `admin.gis-cad.index` & `admin.site-surveys.index` (link "GIS to CAD" & "Survey Lapangan" di sidebar officer) ikut diperbaiki juga secara proaktif (lihat baris "Survey Lapangan & GIS-to-CAD" di atas). Mohon dicoba juga 2 menu itu dari akun officer untuk memastikan.

### L.4 (selesai) `Project::progressSummary()` rewrite + fix auto-advance `approveEvidence()`

Ini bagian paling entangled di Stage 2 -- awalnya dikira murni helper tampilan (read-only), ternyata `ProjectController::approveEvidence()` (baris ~640-766) MEMAKAI hasil `progressSummary()` untuk langsung MENULIS `lops.status_progress` & `projects.status_project` setiap kali admin approve eviden. Jadi dua-duanya harus dikerjakan sekaligus, sinkron.

**Riset consumer dulu (supaya tidak ada yang kebobolan)**: digrep semua pemanggil `Project::progressSummary()` di seluruh kode (bukan `Pt2Lop::progressSummary()` -- itu punya PT2, terpisah total, tidak disentuh). Ketemu 9 titik: `ProjectController::approveEvidence()`, `DashboardController` (5 titik), `DashboardPmController` (2 titik), dan 7 file blade (`admin/projects/index`, `admin/projects/tracking`, `admin/projects/partials/modals`, `admin/projects/partials/project-card`, `admin/evidences/approval`, `admin/program/partials/table`, `pm/program/partials/table`). Hasil grep: SEMUA titik di `DashboardController`/`DashboardPmController` cuma pakai key `progress` (int 0-100) untuk bucket "assigned/waiting/completed" & filter inbox/history (`progress < 100` vs `>= 100`) -- tidak ada yang baca 4 boolean `...Done` langsung di situ. Blade `project-card.blade.php` adalah consumer PALING detail -- baca `persiapanDone`/`instalasiDone`/`pengukuranDone`/`finishingDone` untuk 4 pil indikator warna. Kesimpulan: 10 key balikan lama HARUS dipertahankan persis (nama & tipe), tapi cara hitungnya boleh berubah total.

**Desain baru `Project::progressSummary()`** (di `app/Models/Project.php`):

1. Sumber kebenaran baru: `lops.status_progress` (via relasi `Lop::stage()` -> `project_stages`, sudah eager-load `lop.stage`). `project_stages.sequence` (1-11) jadi acuan utama posisi LOP di alur.
2. **Pengukuran (fix gap Opsi B)**: `pengukuranDone` TIDAK lagi alias `instalasiDone`. Sekarang: kalau LOP sudah di sequence > 8 (Finishing/FI-OGP Golive/Golive), otomatis `true`. Kalau masih di Pengukuran (atau sebelumnya), cek langsung ke `lop_measurement_checks` -- `true` hanya kalau kelima item (`LopMeasurementCheck::ITEMS`) sudah "beres" (`isDone()` = ada eviden ATAU ditandai tidak-ada). Backfill legacy 5-item "tidak ada" untuk LOP yang sudah Finishing+ (dari migration Stage 1) otomatis membuat LOP lama langsung `true` di sini, tidak keblokir.
3. **Progress (persentase)**: sekarang dihitung dari `(sequence - 1) / 10 * 100` (11 tahap = 10 interval) -- Inisiasi = 0%, Golive = 100% persis. Kalau LOP sedang HOLD/DROP, posisi yang dipakai untuk hitung persen adalah `status_progress_before_hold` (bukan status hold/drop itu sendiri, yang tidak punya `sequence`) -- supaya progress bar tidak "hilang"/reset ke 0 cuma karena LOP sedang dijeda atau di-drop sementara (drop tetap bisa direset ke tahap sebelumnya di kemudian hari, sesuai konfirmasi awal soal DROP bukan status final).
4. 4 boolean lama (persiapanDone/instalasiDone/finishingDone) dan key BOQ (materialTotal/instalasiApproved/finishingApproved/finishingTotal) TETAP dihitung persis seperti versi lama (scan evidence+BOQ tidak diubah) -- masih dipakai `project-card.blade.php` untuk 4 pil warna. `stageLabel` sekarang diambil dari `project_stages.label` kalau ada (fallback ke logic lama kalau LOP tidak ada).
5. Key BARU ditambahkan di balikan (tidak mengganggu consumer lama, murni tambahan): `stageCode`, `stageSequence`, `phaseGroup`, `isHold`, `isDrop` -- disiapkan untuk dipakai Stage 3-6 (stepper 11-tahap, badge HOLD/DROP, dst) tanpa perlu re-desain lagi nanti. Ada fallback aman kalau Project belum punya baris LOP sama sekali (edge-case data yatim) -- balik ke formula 4-boolean lama supaya tidak crash.

**Desain baru `ProjectController::approveEvidence()`** (blok "UPDATE STATUS PROGRESS LOP", baris ~706-763):

1. Tiap transisi sekarang di-**gate pada posisi SEKARANG** (`project_stages.sequence` current, bukan status_project/status yang lama) -- approve eviden yang telat atau ganda TIDAK BISA lagi memundurkan atau melompati tahap LOP (dulu: `elseif ($evidence->stage == 'persiapan')` misalnya, jalan begitu saja TANPA cek apakah LOP sudah lewat dari situ).
2. **LOP yang sedang HOLD/DROP sekarang TIDAK disentuh sama sekali** oleh approve eviden -- ini gap yang belum ada di kode lama (kode lama tidak kenal konsep HOLD/DROP karena kolomnya baru ada di Stage 1). Resume dari HOLD/DROP tetap jadi aksi eksplisit terpisah (tombol resume, direncanakan Stage 4/5), bukan efek samping approve eviden.
3. **Fix gap Opsi B yang sesungguhnya**: instalasi selesai (`instalasiDone` + posisi persis di sequence 7) sekarang berhenti dulu di `status_progress = 'pengukuran'` (dulu langsung loncat ke `'finishing'`, skip total). Baru lanjut ke `'finishing'` setelah eviden stage `pengukuran` di-approve DAN `pengukuranDone` (5 item lop_measurement_checks beres) DAN posisi persis di sequence 8.
4. Transisi persiapan->instalasi sekarang juga digate pada `persiapanDone` (dulu longgar -- approve SATU SAJA dari barang_tiba/perizinan sudah cukup memindahkan status_progress, walau syaratnya sebenarnya butuh KEDUANYA approved). Catatan: 5 sub-step Persiapan baru (Inisiasi/Survey/DRM/Perizinan/Material Delivery) + Persiapan Instalasi belum punya UI upload sendiri (itu Stage 4) -- eviden `stage=persiapan` yang ada sekarang (barang_tiba+perizinan) untuk sementara masih dianggap mewakili SELURUH fase itu; begitu approved lengkap, LOP langsung dianggap siap Instalasi (sequence 7). Ini akan direvisi lagi begitu Stage 4 (stepper Persiapan 5-langkah) selesai dibangun.
5. **Perubahan perilaku yang PALING PENTING untuk ditest**: cabang lama "kalau progress==100% -> set status_progress='finishing' DAN status_project='close'" **sudah dihapus total**. Alasannya: dulu 100% = selesai eviden Finishing (step ke-4 dari 4 tahap lama). Sekarang Finishing cuma sequence 9 dari 11 -- masih ada 2 tahap lagi (FI-OGP Golive upload admin, lalu verifikasi Golive oleh SDI, keduanya Stage 5 -- BELUM dibangun) sebelum project sungguh-sungguh selesai/live. Kalau auto-close tetap dipertahankan di titik lama, project akan ditutup PADAHAL belum benar-benar Golive -- jelas salah untuk flow baru. **Konsekuensinya untuk sekarang**: LOP yang eviden Finishing-nya sudah lengkap disetujui akan "berhenti" di `status_progress='finishing'` + `status_project='active'` (TIDAK auto-close) sampai Stage 5 (FI-OGP Golive & Golive) selesai dibangun dan menyediakan cara baru untuk benar-benar menutup project. Trigger notifikasi "Ready UT"/log `project_completed` dipindah dari `progress==100` ke `finishingDone` (key yang secara semantik memang berarti "seluruh eviden wajib s.d. Finishing sudah beres"), jadi notifikasi itu tetap muncul di titik yang sama seperti sebelumnya (tidak berubah kapan munculnya) -- yang berubah HANYA project tidak lagi otomatis ke status `close`.

**2 bug tambahan yang ketemu selagi riset & langsung diperbaiki (efek samping migration Stage 1)**:

- `ImportController::updatePid()` (dipanggil dari form edit PID admin, baris ~842): kalau LOP belum ada, kode lama membuat baris baru dengan `status_progress = 'preparation'` -- kode ini SUDAH TIDAK VALID lagi sebagai FK ke `project_stages.code` sejak migration Stage 1 (nilai `'preparation'` sudah dipetakan jadi `'persiapan_instalasi'` dan tidak pernah dipakai lagi sebagai kode baru). Kalau tidak diperbaiki, form ini akan GAGAL dengan error foreign key constraint setiap kali dipakai untuk PID yang belum punya LOP. Diperbaiki jadi `'inisiasi'` (tahap pertama yang baru, juga sudah jadi DEFAULT kolom ini di DB).
- `DashboardPmController::buildIndexData()` (baris ~45): query `COUNT(CASE WHEN status_progress = 'preparation' ...)` jadi selalu 0 sejak migration Stage 1 (alasan sama seperti di atas). Hasilnya (`total_prep`) ternyata tidak dipakai di manapun di bawahnya saat ini (dead code secara fungsional), jadi tidak ada dampak terlihat -- tapi tetap diperbaiki jadi hitung semua status SELAIN instalasi/finishing/hold/drop, supaya tidak menyesatkan kalau suatu saat dipakai.
- (Dicatat, TIDAK diperbaiki karena memang sudah dead code/tidak pernah dipanggil dari manapun): `app/Jobs/ImportPidJob.php` juga masih punya `status_progress = 'preparation'` hardcode, tapi job class ini tidak di-dispatch dari manapun di seluruh kode (sudah digantikan `PidImportService`+job chunk yang sudah diperbaiki di L.2) -- dibiarkan apa adanya, tidak ada risiko live.

**File yang diubah & sudah disinkronkan (0 rejected)**: `app/Models/Project.php`, `app/Http/Controllers/ProjectController.php`, `app/Http/Controllers/ImportController.php`, `app/Http/Controllers/DashboardPmController.php`.

**PENTING -- perlu ditest**: karena saya tidak bisa live-test, mohon dicoba alur approve eviden dari awal sampai Finishing untuk 1 LOP percobaan (persiapan -> instalasi -> pengukuran -> finishing), sambil perhatikan:
1. Progress bar / persentase di dashboard admin & PM tampil masuk akal (naik bertahap, bukan lompat 0->100% atau nyangkut).
2. Kartu project (`project-card.blade.php`) -- 4 pil indikator warna (persiapan/instalasi/pengukuran/finishing) tetap nyala sesuai urutan yang benar.
3. Setelah eviden instalasi terakhir di-approve, `status_progress` LOP harus jadi `pengukuran` (BUKAN langsung `finishing` seperti dulu) -- bisa dicek lewat halaman manapun yang menampilkan status_progress mentah, atau tanya saya cara ceknya lewat DB kalau perlu.
4. Setelah SEMUA eviden Finishing di-approve, project TIDAK lagi otomatis pindah ke status "close"/selesai -- ini SENGAJA (lihat poin 5 di atas), tapi mohon dikonfirmasi ini sesuai ekspektasi (kalau ternyata kamu butuh cara manual untuk menutup project SEMENTARA sambil menunggu Stage 5 selesai, kabari saya, saya bisa tambahkan tombol close manual darurat di admin).
5. LOP yang sedang HOLD/DROP -- eviden yang di-approve untuk LOP itu (kalau ada) TIDAK boleh mengubah `status_progress`-nya (harus tetap `hold`/`drop`, tidak diam-diam lanjut).

### L.5 Sisa Stage 2 yang BELUM dikerjakan

Stage 2 (backend logic) sekarang SELESAI SEMUA sub-itemnya: assign waspang/teknisi bugfix (L.1), PidImportService (L.2), middleware role-based (L.3), progressSummary + approveEvidence (L.4). Lanjut ke **Stage 3** (UI admin untuk kelola 3 tabel master baru: project_stages, kendala_categories, permit_categories) menunggu hasil testing L.3.5 dan poin testing di L.4 di atas.

## M. Catatan operasional: queue worker harus di-restart setelah update kode

Saat testing upload PID pertama kali sesudah L.4 disinkronkan, muncul error FK `status_progress = 'preparation'` -- padahal kode di `PidImportService.php` sudah benar (`'inisiasi'`). Penyebabnya BUKAN bug kode, tapi operasional: upload PID diproses lewat queue (`QUEUE_CONNECTION=database`, job `ProcessPidImportJob` dijalankan background oleh proses `php artisan queue:work`). Proses worker Laravel meng-cache kode aplikasi di memori saat start -- perubahan file TIDAK otomatis kepakai sampai worker di-restart (`php artisan queue:restart`, atau matikan & jalankan ulang `queue:work` manual).

**Ini bukan cuma catatan sekali pakai** -- berlaku untuk SETIAP perubahan kode yang saya sinkronkan ke depannya yang menyentuh apapun yang dijalankan lewat queue (import PID/BOQ, generate BAUT/LACT dokumen, dan job lain yang pakai `ShouldQueue`). Perubahan controller/route/view TIDAK butuh restart (jalan per-request di web server, bukan proses panjang), tapi perubahan Service/Job yang dipanggil dari queue butuh. Kalau habis ini saya bilang "sudah disinkronkan" untuk perubahan yang menyentuh import atau job background, mohon restart worker dulu sebelum test.

Sudah dikonfirmasi: setelah restart worker, upload PID & BOQ berhasil normal.

## N. Eksekusi Stage 3 (2026-09-08) — UI Admin kelola 3 master data baru

Stage 3 SELESAI dikerjakan & disinkronkan (9 file, 0 rejected): UI CRUD sederhana untuk 3 tabel master yang dibuat di Stage 1 (`project_stages`, `kendala_categories`, `permit_categories`), supaya admin bisa kelola sendiri tanpa perlu migration/ubah kode setiap kali ada tahapan/kategori baru.

**Pola yang dipakai**: disamakan persis dengan CRUD master data yang sudah ada (`PackageController`/`admin/packages/index.blade.php`) -- 1 halaman list + modal tambah/edit, tanpa halaman terpisah. `kendala_categories` & `permit_categories` bentuknya identik (name + sort_order + is_active), jadi controller & view-nya juga dibuat kembar (`KendalaCategoryController`/`PermitCategoryController`, masing-masing view sendiri) -- konsisten dengan pola kode yang sudah ada (Designator/Package/DesignatorPrice juga dipisah per-controller walau mirip, bukan digabung 1 controller generic).

**Role akses**: disatukan ke dalam grup middleware "Master Designator / Package / Price" yang sudah ada (`role:admin,superadmin,super_tif` -- officer di-exclude), karena ini sama-sama "master data alur/konfigurasi", bukan data transaksional harian. Kalau ternyata officer JUGA perlu akses ke sini (misalnya untuk nambah kategori kendala baru), tinggal ubah 1 baris role di `routes/web.php` -- kabari saya kalau iya.

**Penanganan khusus `project_stages`** (beda dari 2 tabel kategori lainnya, karena `code`-nya benar-benar dipakai sebagai FK oleh `lops.status_progress`):

1. Kolom **`code` HANYA bisa diisi saat membuat tahapan baru, TIDAK BISA diubah lagi** sesudahnya (di form edit field-nya read-only) -- supaya tidak ada LOP yang "nyasar" gara-gara code-nya berubah. Ini juga dijaga di level DB (FK RESTRICT), tapi divalidasi lebih dulu di UI biar pesannya ramah.
2. Tahapan **`hold`** & **`drop`** tidak boleh dihapus ATAU dinonaktifkan lewat UI ini sama sekali (tombol Hapus disembunyikan khusus utk 2 kode ini) -- karena dipakai LANGSUNG oleh nama code-nya di `Project::progressSummary()` & `ProjectController::approveEvidence()` (lihat L.4). Kalau dihapus/dinonaktifkan, sebagian besar logic status LOP akan rusak.
3. Tahapan APAPUN yang sedang dipakai minimal 1 LOP (baik lewat `status_progress` aktif maupun `status_progress_before_hold` saat LOP itu sedang HOLD/DROP) tidak bisa dihapus -- tombol Hapus tetap ada tapi submit-nya akan ditolak dengan pesan jelas ("nonaktifkan saja"), bukan error SQL mentah.
4. Nonaktifkan (toggle Aktif/Nonaktif) TIDAK menghapus data & TIDAK mempengaruhi LOP yang sudah ada di tahapan itu -- cuma menyembunyikannya dari pilihan untuk LOP baru nantinya (dipakai di Stage 4, stepper waspang).

**`kendala_categories` & `permit_categories`** lebih longgar -- keduanya `ON DELETE SET NULL` di level DB (dikonfirmasi lewat migration), jadi hapus kategori yang sudah pernah dipakai TIDAK error, cuma baris lama (kendala/LOP) kehilangan kategorinya (jadi NULL). UI cukup kasih peringatan di dialog konfirmasi, tidak perlu diblokir keras seperti `project_stages`.

**File baru**: `app/Http/Controllers/ProjectStageController.php`, `app/Http/Controllers/KendalaCategoryController.php`, `app/Http/Controllers/PermitCategoryController.php`, `resources/views/admin/project-stages/index.blade.php`, `resources/views/admin/kendala-categories/index.blade.php`, `resources/views/admin/permit-categories/index.blade.php`.

**File diubah**: `routes/web.php` (15 route baru, 3 resource x 5 route: index/store/update/toggle-active/destroy), `resources/views/admin/components/sidebar.blade.php` & `sidebar-mobile.blade.php` (menu baru "Master Alur PT3" dengan 3 sub-link, diletakkan persis di bawah menu "Master Designator").

**PENTING -- perlu ditest** (saya tidak bisa live-test UI sama sekali):
1. Menu "Master Alur PT3" muncul di sidebar admin/superadmin/super_tif, TIDAK muncul utk officer.
2. Buka masing-masing dari 3 sub-menu, coba tambah data baru, edit, toggle aktif/nonaktif.
3. Khusus Tahapan Alur: coba edit tahapan yang SUDAH ADA (misal ubah label "Instalasi" jadi lain) -- pastikan field Kode di form edit tidak bisa diketik/diubah (read-only), dan setelah simpan code-nya tetap sama.
4. Khusus Tahapan Alur: coba hapus tahapan yang SEDANG DIPAKAI LOP (misal 'inisiasi' kalau sudah ada LOP baru di situ dari test upload PID) -- harus muncul pesan error "masih dipakai", BUKAN error SQL mentah, dan datanya tidak boleh terhapus.
5. Pastikan tombol Hapus TIDAK muncul sama sekali untuk baris Hold & Drop.

Kalau semua lancar, saya lanjut ke **Stage 4** (UI waspang: stepper baru, halaman-halaman sub-step Persiapan, tombol kendala universal, picker kategori Perizinan, halaman Pengukuran dengan mekanisme radio "tidak ada" per 5 item).

### N.1 Fix: menu "Master Alur PT3" tidak muncul untuk role super_tif

Dilaporkan menu cuma muncul di sidebar superadmin. Penyebabnya: ternyata role **super_tif** (dan **officer**) punya file component sidebar SENDIRI yang terpisah total dari `resources/views/admin/components/sidebar*.blade.php` (`resources/views/super_tif/components/sidebar*.blade.php` -- lihat pemilihan file di `layouts/admin.blade.php` baris ~34-41: `@if role===super_tif` pakai sidebar super_tif sendiri, `@elseif role===officer` pakai sidebar officer sendiri, selain itu baru pakai sidebar admin). Role **admin** & **superadmin** memang berbagi file yang sama (makanya superadmin langsung kelihatan begitu saya edit file admin), tapi saya lupa super_tif punya fork sendiri dari sidebar itu (mirip kasus officer yang sudah pernah ditemukan sebelumnya di L.3) -- jadi menu baru cuma ke-inject di file admin, tidak ikut ke file super_tif.

**Fix**: block "Master Alur PT3" yang sama ditambahkan juga ke `resources/views/super_tif/components/sidebar.blade.php` & `sidebar-mobile.blade.php`. Officer TIDAK ditambahkan (sengaja, sesuai role gate yang sudah ditetapkan -- officer memang di-exclude dari master data ini, sama seperti Master Designator). File yang disinkronkan (0 rejected): 2 file sidebar super_tif di atas.

Mohon dites ulang login sebagai **admin** (role biasa, bukan superadmin) dan **super_tif** -- keduanya sekarang seharusnya sudah bisa lihat menu "Master Alur PT3" juga.

### N.2 Koreksi: "Master Alur PT3" ternyata memang KHUSUS superadmin (bukan bug)

Setelah dikonfirmasi ke pemilik project, ternyata N.1 di atas salah arah -- menu "Master Alur PT3" MEMANG dimaksudkan khusus role **superadmin saja**, role lain (admin, super_tif, officer) TIDAK perlu akses sama sekali. Jadi perbaikan N.1 (menambahkan ke sidebar super_tif) **dibatalkan/di-revert**, dan sebagai gantinya:

1. **routes/web.php**: 15 route `admin.project-stages.*`/`admin.kendala-categories.*`/`admin.permit-categories.*` dipisah dari grup "Master Designator" (`admin,superadmin,super_tif`) ke grup middleware SENDIRI dengan `role:superadmin` saja.
2. **`resources/views/admin/components/sidebar.blade.php` & `sidebar-mobile.blade.php`**: karena role `admin` & `superadmin` berbagi file sidebar yang SAMA PERSIS, blok menu "Master Alur PT3" sekarang dibungkus `@if(auth()->user()->role === 'superadmin') ... @endif` -- pola yang sama seperti blok "User Management" yang sudah lebih dulu ada di file ini (baris ~830, khusus superadmin juga).
3. **`resources/views/super_tif/components/sidebar.blade.php` & `sidebar-mobile.blade.php`**: blok menu yang sempat ditambahkan di N.1 dihapus lagi sepenuhnya (revert bersih, dikonfirmasi lewat pengecekan `x-data=` count kembali ke jumlah semula sebelum N.1).

Semua file (5 file: `routes/web.php` + 4 sidebar) sudah disinkronkan ulang, 0 rejected, dan sudah dicek langsung di server bahwa grup route baru (`role:superadmin`) dan blok `@if superadmin` di sidebar admin sudah kepasang dengan benar, serta sidebar super_tif sudah bersih dari sisa-sisa menu ini.

**Mohon dites ulang sekali lagi**: menu "Master Alur PT3" HANYA muncul untuk superadmin; admin biasa, super_tif, dan officer semuanya TIDAK boleh melihatnya lagi (baik di sidebar maupun kalau mencoba akses URL-nya langsung harus 403).

---

## O. Stage 4a -- Pengukuran: gate nyata (N/A toggle + fix key mismatch)

Sebelum Stage 4, dilakukan riset mendalam (via subagent, read-only) atas seluruh UI mobile Waspang (`WaspangController`, 15-an blade view, layout, partial stepper/bottom-nav, kendala UI) untuk memetakan gap antara arsitektur LAMA (evidence-driven, `evidences.stage` enum 4 nilai) dengan arsitektur BARU hasil Stage 1-3 (`project_stages` 11-tahap, `lop_measurement_checks`, `kendala_categories`, `permit_categories`). Temuan kunci:

1. Stepper mobile masih hardcode 4 tahap (Persiapan/Instalasi/Pengukuran/Finishing), belum tahu apa-apa soal 11-tahap baru.
2. **Halaman Pengukuran (`waspang/steps/pengukuran.blade.php`) pakai nama item YANG BEDA dari model**: `otdr_sor`/`lainnya` vs `LopMeasurementCheck::ITEMS` yang sebenarnya `file_sor`/`eviden_lainnya`. Tidak ada UI toggle "Tidak Ada" (N/A) sama sekali.
3. Kendala hanya bisa dilaporkan dari halaman Inbox, pilihan `issue_type` hardcode 6 opsi, tidak terhubung ke tabel `kendala_categories` (32 baris) atau kolom `kendala_category_id`.
4. `permit_categories` (12 baris) + `lops.permit_category_id` sudah ada tapi belum ada UI sama sekali yang memakainya.
5. Tahap 1-6 (inisiasi/survey/drm/perizinan/material_delivery/persiapan_instalasi) & 10-11 (FI-OGP Golive/Golive) belum punya halaman waspang sama sekali -- UI mobile yang ada sekarang cuma menutupi apa yang sekarang jadi tahap 6-9 (pakai penamaan lama).
6. `LopStageHistory` (tabel `lop_stage_histories`) sudah ada modelnya, sudah ter-relasi, tapi TIDAK ADA satupun kode yang menulis ke situ -- scaffolding murni, belum dipakai.

**Temuan PALING KRITIS** (ditemukan saat menelusuri ulang `Project::progressSummary()` hasil Stage 2): `pengukuranDone` (gate nyata sejak Stage 2) membaca murni dari tabel `lop_measurement_checks` -- tapi TIDAK ADA SATUPUN kode yang menulis baris ke tabel itu untuk LOP yang SEDANG BERJALAN (hanya migration backfill Stage 1 yang mengisi utk LOP LAMA yang sudah lewat Pengukuran). Akibatnya: **setiap LOP baru yang sampai ke tahap Pengukuran akan macet permanen di situ selamanya**, walau semua eviden OTDR/OPM/Kedalaman sudah di-approve admin -- karena `LopMeasurementCheck::where('lop_id',...)->get()` selalu kosong. Ini adalah regresi tersembunyi dari Stage 2 yang baru kelihatan sekarang, dan jadi prioritas #1 Stage 4.

### Keputusan desain

Daripada mengerjakan SELURUH scope Stage 4 (stepper baru 11-tahap, 5 halaman sub-step Persiapan baru, kendala universal, picker perizinan) sekaligus dalam 1 batch besar -- yang beresiko tinggi & sulit ditest bertahap -- Stage 4 dipecah jadi beberapa sub-tahap. **Stage 4a (bagian ini) fokus HANYA pada memperbaiki gate Pengukuran**, karena itu satu-satunya bagian yang BENAR-BENAR memblokir alur (bug fungsional), bukan sekadar UI yang belum lengkap.

Perubahan Stage 4a:

1. **`app/Http/Controllers/WaspangController.php`**:
   - `pengukuran()`: sekarang eager-load `lop`, dan kirim `$measurementChecks` (baris `lop_measurement_checks` existing utk LOP ini, keyBy `item_key`) ke view. Riwayat revisi (`$revisionHistories`) utk item `file_sor`/`eviden_lainnya` sekarang `whereIn` menggabungkan nama lama+baru supaya histori lama tidak hilang.
   - Method baru `toggleMeasurementCheck(Request $request, $project, $itemKey)`: waspang menandai 1 item sebagai "Tidak Ada" (N/A) atau membatalkannya. HANYA boleh ditandai N/A kalau item itu BELUM punya eviden sama sekali (apapun statusnya) -- kalau sudah ada foto/file, harus dihapus dulu. Upsert ke `LopMeasurementCheck` (`is_not_applicable`, `note`, `checked_by`), dicatat ke `ProjectActivityService::log()`.
2. **`app/Http/Controllers/ProjectController.php`** (`approveEvidence()`): tambah hook -- begitu admin approve 1 eviden `stage='pengukuran'`, evidence_type-nya (termasuk alias nama lama `otdr_sor`/`lainnya`) dipetakan ke `item_key` kanonik, lalu `LopMeasurementCheck::updateOrCreate(['lop_id'=>...,'item_key'=>...], ['evidence_id'=>$evidence->id_evidence,'is_not_applicable'=>false])`. **Inilah fix regresi kritis di atas** -- sekarang setiap approve eviden Pengukuran otomatis mengisi tabel gate-nya.
3. **`routes/web.php`**: route baru `POST /waspang/projects/{project}/measurement-check/{itemKey}` -> `waspang.measurement-check.toggle`.
4. **`resources/views/waspang/steps/pengukuran.blade.php`**: array `$pengukuranItems` sekarang pakai `type` KANONIK (`otdr`/`file_sor`/`opm`/`kedalaman`/`eviden_lainnya`, samakan persis `LopMeasurementCheck::ITEMS`), tiap item juga punya `legacy_types` (alias nama lama) dipakai di semua query eviden (`whereIn`) supaya upload lama tetap kehitung/tampil. Tiap card sekarang: kalau item sudah ditandai N/A -> tampil badge abu-abu "N/A" + kotak info + tombol "Batalkan"; kalau belum ada eviden sama sekali -> muncul form kecil "Tandai Tidak Ada" (catatan opsional) di bawah tombol Upload Tambahan; kalau sudah ada eviden -> form itu disembunyikan (harus hapus fotonya dulu baru bisa ditandai N/A).

**Sengaja TIDAK diubah di Stage 4a** (menyusul di sub-tahap berikutnya): stepper 4-tahap hardcode, 5 halaman sub-step Persiapan baru, kendala universal + `kendala_category_id`, picker `permit_categories`, `LopStageHistory` logging. Semua ini murni penambahan UI/fitur (bukan bug pemblokir), jadi aman dikerjakan terpisah & bertahap sesuai instruksi user.

### Checklist testing Stage 4a (mohon dicoba manual)

1. Buka halaman Pengukuran (mobile waspang) utk 1 LOP yang belum lengkap semua item -- pastikan 5 kartu tampil dgn label benar: OTDR, File (.SOR), OPM, Kedalaman Galian, Eviden Lainnya.
2. Coba tandai 1 item (misal "Eviden Lainnya") sebagai "Tidak Ada" (isi catatan opsional) -> submit -> kartu berubah jadi badge abu-abu "N/A", tombol Upload Tambahan & form N/A hilang, muncul tombol "Batalkan".
3. Klik "Batalkan" -> kartu kembali normal (bisa upload / tandai N/A lagi).
4. Upload 1 foto ke item OTDR -> pastikan form "Tandai Tidak Ada" hilang dari kartu itu (karena sudah ada eviden, tidak boleh ditandai N/A sebelum foto dihapus).
5. **PENTING**: sebagai admin, approve eviden OTDR/File SOR/OPM/Kedalaman yg sudah diupload waspang -- untuk LOP UJI COBA, tandai N/A pada item2 sisanya (atau upload+approve semua 5), sampai kelima item "selesai" (approved ATAU N/A) -> pastikan `Lop.status_progress` LOP tsb otomatis pindah dari `pengukuran` ke `finishing` (cek di halaman tracking/detail project admin, atau langsung di DB). Ini validasi utk fix regresi kritis di atas -- sebelum Stage 4a, LOP TIDAK PERNAH bisa lolos dari Pengukuran sama sekali.
6. Cek foto/file yang sebelumnya sudah diupload dgn nama lama (`otdr_sor`/`lainnya`, kalau ada data lama) tetap tampil normal di kartu "File (.SOR)"/"Eviden Lainnya" (histori & foto tidak hilang).

### Belum dikerjakan (sisa scope Stage 4, menyusul)

- 5 halaman sub-step Persiapan baru (inisiasi/survey/drm/perizinan/material_delivery) + halaman Persiapan Instalasi.
- Tombol kendala universal (semua halaman step, bukan cuma Inbox) + wiring `kendala_category_id` (pakai `KendalaCategory::active()`).
- Picker kategori perizinan (`permit_categories`) di tahap Perizinan.
- (Opsional, didiskusikan lagi kalau relevan) mulai isi `LopStageHistory` saat transisi tahap.

---

## P. Stage 4b -- Redesain visual penuh seluruh halaman mobile Waspang

Setelah Stage 4a disinkronkan, user melaporkan belum bisa mencoba alur approve karena "tampilan waspang masih belum sesuai dengan flow yang baru", dan minta sekalian dibuatkan modern/clean/profesional/menarik. Diklarifikasi lewat 2 pertanyaan pilihan: (1) scope -- dipilih **redesain penuh SEMUA halaman waspang** (bukan cuma stepper/Pengukuran saja), (2) gaya -- dipilih **segarkan total (palet & komponen baru)**, bukan sekadar rapikan gaya lama, asal tetap 1 keluarga aplikasi dgn admin/PM.

### Root cause "belum sesuai flow baru"

Partial `waspang/partials/stepper.blade.php` (dipakai di 4 halaman step: Persiapan/Instalasi/Pengukuran/Finishing) sepenuhnya menghitung ulang sendiri dari scan `evidences` lama -- TIDAK PERNAH membaca `lops.status_progress`/`project_stages` sama sekali. Jadi walau backend (Stage 2-4a) sudah benar2 memindah LOP ke tahap yg tepat, waspang di lapangan tidak pernah melihat cerminan posisi itu di UI-nya -- makanya terasa "gak sesuai flow baru". Bukan bug transisi, murni tampilan yg tidak pernah dihubungkan ke model baru.

### Desain sistem baru (dipakai konsisten di SEMUA halaman)

- **Warna utama**: indigo (gradient `from-indigo-600 via-indigo-600 to-blue-600` utk header), gantikan flat `blue-700` lama. Badge status (approved/pending/rejected/dsb) TETAP pakai warna semantik lama (hijau/amber/merah/abu) -- yang diganti cuma warna brand/CTA.
- **Background**: body `bg-[#eef0fb]`, container utama `bg-[#f5f6fb]` (gantikan `#f7f6f2` lama).
- **SweetAlert2 confirmButtonColor**: `#4F46E5` (indigo-600) gantikan `#1D4ED8` (blue-700) lama.
- **Kartu**: putih, `rounded-2xl`/`rounded-3xl`, border tipis, shadow lembut -- pola lama dipertahankan krn sudah baik, cuma direfresh warnanya.
- Font (Plus Jakarta Sans) & Font Awesome 6.4.0 tetap dipakai (sudah global lewat `layouts/waspang.blade.php`), beberapa ikon inline SVG (Heroicons) diganti Font Awesome di beberapa halaman utk konsistensi.

### Perbaikan FUNGSIONAL pada stepper (bukan cuma kosmetik)

`waspang/partials/stepper.blade.php` ditulis ulang total: sekarang panggil `$project->progressSummary()` (sumber tunggal kebenaran posisi LOP, sudah dipakai backend sejak Stage 2) lalu tampilkan:
1. **Chip "Posisi: {label tahap sebenarnya}"** dgn titik warna sesuai `project_stages.color` (peta warna Tailwind STATIS di `@php`, BUKAN interpolasi `"bg-{$color}-400"` -- supaya tetap ke-compile Tailwind JIT/Vite, karena kelas dinamis hasil interpolasi runtime tidak akan ke-scan build tool).
2. **Banner HOLD/DROP** kalau LOP sedang dijeda/dibatalkan (`isHold`/`isDrop`).
3. **Progress bar tipis keseluruhan** dari `progress` (0-100%, berdasarkan sequence 1-11).
4. **4 segmen mini-stepper** (Persiapan/Instalasi/Pengukuran/Finishing -- 4 halaman fisik yg SUDAH ADA) yg status selesai/terbuka-nya sekarang dihitung dari **posisi sequence efektif** (`effectiveStageSequence`, fallback ke tahap sebelum HOLD/DROP), bukan lagi scan evidence manual -- Persiapan (halaman ini mewakili sequence 1-6: inisiasi s.d. persiapan_instalasi, karena 5 sub-halamannya belum dibangun) dianggap selesai kalau sequence > 6, Instalasi selesai kalau > 7, dst.

**Perubahan backend pendukung** (`app/Models/Project.php`, method `progressSummary()`): ditambah 5 key baru ke array return -- `effectiveStageCode`, `effectiveStageSequence`, `effectiveStageLabel`, `effectiveStageColor`, `effectivePhaseGroup` -- adalah posisi LOP yg SUDAH memperhitungkan fallback HOLD/DROP (logika ini sebelumnya cuma dipakai internal utk hitung `progress`, sekarang diekspos supaya view/halaman lain bisa pakai tanpa duplikasi logika). `WaspangController.php`: 4 method (`persiapan`, `instalasi` lewat `getAssignedProject()`, `finishing`, `pengukuran`) sekarang eager-load `lop.stage` supaya stepper tidak N+1 query per card.

### Halaman yang direstyle (semua file, 100% logika/route/JS dipertahankan -- murni reskin)

Saya kerjakan langsung: `layouts/waspang.blade.php`, `waspang/partials/stepper.blade.php` (tulis ulang, lihat di atas), `waspang/partials/bottom-nav.blade.php`, `waspang/partials/revision-history.blade.php`, `waspang/show.blade.php` (Persiapan), `waspang/steps/pengukuran.blade.php` (rebrand warna di atas fix fungsional Stage 4a yg sudah ada).

Sisanya didelegasikan ke 3 sub-agent paralel (briefing: 2 halaman referensi di atas + design system + larangan keras ubah logika/route/nama variabel/JS, wajib self-check keseimbangan `@if/@endif`, `@foreach/@endforeach`, `@php/@endphp`, `<div>/</div>` sebelum selesai) -- semua hasilnya saya verifikasi ulang sendiri (re-check keseimbangan + grep variabel/route kunci) sebelum disinkronkan:
- `waspang/steps/instalasi.blade.php`, `waspang/steps/finishing.blade.php`
- `waspang/dashboard.blade.php`, `waspang/inbox.blade.php` (termasuk modal Laporkan Kendala), `waspang/ready-ut.blade.php`
- `waspang/steps/review-final.blade.php`, `waspang/notifications.blade.php`, `waspang/profile.blade.php`

Total 16 file disinkronkan (14 blade + `Project.php` + `WaspangController.php`), semua lolos verifikasi keseimbangan struktur & 0 rejected saat commit ke server.

### Checklist testing Stage 4b (mohon dicoba manual)

1. Buka semua halaman waspang (Home, Inbox, Ready UT, Notif, Profil, Persiapan, Instalasi, Pengukuran, Finishing, Review Final) -- pastikan tidak ada tampilan rusak/berantakan, semua tombol & foto/upload masih berfungsi normal seperti sebelumnya (cuma beda warna/tampilan).
2. Di halaman step manapun (Persiapan/Instalasi/Pengukuran/Finishing), cek header baru -- harus muncul chip **"Posisi: <nama tahap sebenarnya>"** yang sesuai dgn `status_progress` LOP tsb saat ini (bisa dicek silang di halaman tracking admin).
3. Lanjutkan test approve eviden Pengukuran dari checklist Stage 4a sebelumnya (poin 5) -- sekarang harusnya sudah lebih mudah dites krn tampilannya sudah jelas menunjukkan posisi LOP.
4. Coba LOP yg sedang di-HOLD (kalau ada) -- pastikan banner kuning "LOP di-HOLD" muncul di header step manapun.

---

## Q. Stage 4c -- Koreksi desain (flat, tanpa gradient) + stepper 5-tahap + fix assign->Survey

Setelah Stage 4b, user mengoreksi 2 hal & memberi spesifikasi fungsional besar utk sub-step Persiapan:

### Q.1 Koreksi desain (dikerjakan penuh sesi ini)

- **Tanpa gradient** -- semua header `bg-gradient-to-br from-indigo-600 via-indigo-600 to-blue-600` diganti FLAT `bg-[#1565D8]` (persis hex yg diminta: Primary `#1565D8`, Secondary `#2563EB`, Background `#F8FAFC`, Card putih, Text "dark navy" -> dipetakan ke keluarga `slate-*` Tailwind, semua `gray-*` di 14 file waspang diganti `slate-*` biar 1 keluarga warna dgn background baru).
- **Stepper diringkas jadi 5 kolom**: Persiapan / Instalasi / Pengukuran / Finishing / **Selesai** (kolom baru, mewakili sequence 10-11 FI-OGP Golive+Golive -- belum ada halaman waspang sendiri, jadi murni indikator status/tidak bisa diklik). Aturan warna PERSIS sesuai brief: aktif = circle+text biru & background putih; selesai = icon check HIJAU; belum aktif = abu-abu. State tambahan "ditolak" (merah) dipertahankan di luar 3 aturan itu krn tetap berguna (menandakan ada eviden yg direject admin).
- Chip "Posisi: <tahap sebenarnya>" + banner HOLD/DROP + (versi sebelumnya progress bar tipis, sekarang disederhanakan mengikuti stepper baru) tetap dipertahankan krn itu yang menjawab keluhan "belum sesuai flow baru" di awal.
- File yg disentuh: 14 file blade waspang (semua yg direstyle di Stage 4b) + `layouts/waspang.blade.php` -- lewat 1 script python (subtitusi kelas Tailwind sistematis: `indigo-600`->`[#1565D8]`, `indigo-700`->`[#0F4FAF]` utk hover, `indigo-50..500`->`blue-50..500`, `gray-*`->`slate-*`, shadow ber-warna->`shadow-slate-900/10` netral) + rewrite manual `stepper.blade.php` & fix 1 casualty di `bottom-nav.blade.php` (tombol FAB Survey sempat kehilangan warna latar krn pola gradient-nya beda dari file lain, sudah diperbaiki & diverifikasi ulang).
- **Catatan penting**: tombol FAB "Survey" di bottom-nav (mengarah ke `route('surveyor.index')`) adalah modul **Site Survey/GIS milik role SDI** (beda dari "Survey" yang dimaksud user di poin Q.2 di bawah, yaitu tahap ke-2 Persiapan utk input BOQ material). Belum diubah krn di luar scope pesan ini -- perlu diklarifikasi apakah FAB itu perlu diarahkan ulang atau dibiarkan (2 fitur "Survey" yg berbeda makna, berpotensi membingungkan waspang).

### Q.2 Klarifikasi alur status + spesifikasi 5 sub-step Persiapan (BELUM dikerjakan, rencana berikut)

User menjelaskan alur status yg benar:
1. **inisiasi**: saat admin upload PID+BOQ (sudah sesuai kode existing).
2. **survey**: begitu LOP di-assign ke waspang. **Sudah diimplementasikan sesi ini** -- `ProjectController::assignWaspang()` sekarang otomatis memindah `lops.status_progress` dari `inisiasi` ke `survey` saat assignment ke role waspang dibuat (guard: hanya kalau LOP masih PERSIS di `inisiasi` & bukan PT2, supaya tidak memundurkan/menimpa LOP yg sudah lebih maju saat reassign). Lalu waspang input BOQ material + quantity manual (mirip Mode Survey Teknisi PT2) ATAU upload file Excel BOQ sesuai template Bulk Import BOQ.
3. **drm**: begitu ada hasil survey, lanjut "Proses DRM" -- upload pdf/foto, pola kartu upload seperti step existing.
4. **perizinan**: pilih kategori dari master `permit_categories`, upload eviden, WAJIB update **kronologi** berkala (setiap aktivitas input catatan + tanggal lewat date picker). Kalau sudah selesai, ada radio "Perizinan Selesai" -> muncul upload **Hasil BA KP** (pdf) + eviden foto.
5. **material_delivery**: upload eviden foto + deskripsi opsional.

Lanjut Persiapan Instalasi - Finishing tetap seperti existing (tidak berubah).

**Tambahan universal**: tombol "Update Kendala" harus ada di SETIAP halaman/activity (bukan cuma Inbox), dengan pilih kategori dari master `kendala_categories`, tulis deskripsi/kronologi, upload eviden.

### Rencana eksekusi (belum dikerjakan, tahap berikutnya)

Mengingat scope-nya besar (4 halaman baru + 1 fitur kronologi + kendala universal, perlu migration baru), akan dikerjakan bertahap spt pola sebelumnya:
1. **Riset dulu** (read-only): pola "Mode Survey Teknisi PT2" (`TeknisiPt2Controller` + view-nya) & mekanisme "Bulk Import BOQ" existing di `ImportController`, supaya halaman Survey baru (poin 2) konsisten dgn pola yg sudah ada, bukan bikin pola baru dari nol.
2. **Migration baru**: tabel kronologi perizinan (`lop_perizinan_logs` atau serupa: lop_id, note, activity_date, created_by), kolom `perizinan_completed_at` di `lops` (utk radio "Perizinan Selesai"). Eviden (BA KP, DRM, Material Delivery) rencana REUSE tabel `evidences` yg sudah ada -- cukup lebarkan validasi `stage` di `uploadEvidence()` utk terima kode baru (`survey`,`drm`,`perizinan`,`material_delivery`) yg PERSIS sama dgn `project_stages.code`, supaya tidak perlu infrastruktur upload baru dari nol.
3. Bangun halaman satu-satu: DRM & Material Delivery dulu (paling sederhana, reuse penuh infrastruktur eviden existing), lanjut Perizinan (kronologi + BA KP, paling kompleks), lalu Survey (input BOQ manual/bulk, perlu riset pola PT2 dulu).
4. Kendala universal (wiring `kendala_category_id` + tombol di semua halaman) sbg tahap terpisah setelah ke-4 halaman di atas selesai.

---

## R. Stage 4d -- Build 5 sub-step Persiapan (SELESAI, sesi ini)

Riset: dikonfirmasi TomSelect (v2.3.1, CDN jsdelivr, sudah dipakai admin/pm/sdi via `layouts/admin.blade.php` dkk) sbg picker designator -- dipakai ulang utk Survey (bukan reinvent pola filter-list manual PT2). Template Bulk Import BOQ admin (matriks 1 kolom/LOP) dikonfirmasi TIDAK cocok utk konteks 1 project -- dibuatkan template flat 2 kolom terpisah khusus waspang.

**Migration baru (2):**
- `2026_09_08_091000_create_lop_kronologis_table.php` -- tabel `lop_kronologis` (Schema::create, gaya sama `lop_measurement_checks`): log kronologi generik, 1 baris per entri, `stage_code` bebas (tidak di-FK ke project_stages spy fleksibel dipakai step lama juga).
- `2026_09_08_091100_add_persiapan_baru_columns_to_lops_and_project_issues.php` -- raw ALTER (gaya sama migration 090300/090400): `lops.perizinan_completed_at` (timestamp nullable) + `project_issues.stage_code` (varchar nullable, tag kendala per sub-step).

**Model baru/diubah:** `LopKronologi` (baru, relasi `lop()`/`project()`/`creator()`); `Lop` (+fillable `perizinan_completed_at` + relasi `kronologis()`); `ProjectIssue` (+fillable `stage_code`).

**FIX KRITIS `Project::progressSummary()`** (kelas bug SAMA dgn regresi gate Pengukuran Stage 2/4a): `persiapanDone` sebelumnya HANYA dihitung dari eviden lama `stage=persiapan` type `barang_tiba`/`perizinan` -- flow baru (Survey/DRM/Perizinan/Material Delivery) TIDAK PERNAH menulis eviden dgn stage itu lagi, jadi LOP flow baru tidak akan PERNAH lolos Persiapan -> Finishing/Ready UT macet permanen kalau tidak diperbaiki. Perbaikan: `$sequence` (posisi project_stages) dipindah dihitung LEBIH AWAL (sebelum bag. Persiapan, bukan lagi di bag. Instalasi), lalu `persiapanDone = true` otomatis kalau `sequence > 6` (sudah di persiapan_instalasi/lebih), fallback ke 2 boolean eviden lama HANYA utk LOP lama/kode tak dikenal. Patch SEJENIS juga diterapkan di `WaspangController::isProjectReadyUt()` (dashboard/ready-ut/inbox count) & `isPersiapanUploaded()` (gate halaman Instalasi, ambang `sequence > 5`).

**`WaspangController` -- method baru:** `storeBoqItemManual`/`deleteBoqItemSurvey`/`downloadBoqTemplateWaspang`/`importBoqExcelWaspang` (Survey, pakai PhpSpreadsheet yg sudah ada di composer.json), `finishSurvey`/`finishDrm`/`finishMaterialDelivery` (transisi status_progress manual per tombol "Selesai ...", masing2 ada syarat minimal data terisi dulu), `updatePerizinanCategory`, `togglePerizinanSelesai` (wajib ada eviden + kronologi, upload BA KP pdf, transisi ke material_delivery), `storeKronologi` (universal, dipakai semua step). `uploadEvidence()` stage validation dilebarkan (+survey/drm/perizinan/material_delivery). `storeIssue()` diupgrade dukung `kendala_category_id` (master) TAPI `issue_type` teks bebas TETAP diterima (nullable, salah satu wajib) -- supaya form kendala lama di `inbox.blade.php` (yg belum diupgrade, masih kirim `issue_type` select statis) tidak ikut patah.

**Routing:** 11 route baru di grup `role:waspang` (survey.boq.*, survey.finish, drm.finish, perizinan.category/selesai, material-delivery.finish, kronologi.store).

**View:** `waspang/show.blade.php` REWRITE TOTAL jadi 5 accordion (Inisiasi read-only / Survey tab manual+import BOQ / DRM upload / Perizinan kategori+eviden+kronologi+radio selesai+BA KP / Material Delivery upload), tiap sub-step aktif dapat 2 tombol universal "Lapor Kendala" + "Update Kronologi". Partial baru: `kendala-modal.blade.php`, `kronologi-modal.blade.php` (modal global 1x include, dipanggil `openKendalaModal(stageCode,label)`/`openKronologiModal(...)` dari tombol manapun), `evidence-photo-grid.blade.php` (grid foto+reject-overlay diekstrak dari pola lama spy tidak 3x copy-paste), `step-action-buttons.blade.php`. `layouts/waspang.blade.php` +CDN TomSelect. `stepper.blade.php`: fix bug laten `$isStep1` (sebelumnya selalu false krn cek `currentRouteName() === 'waspang.projects.show'`, padahal route itu cuma dipakai utk redirect & tidak pernah jadi current route saat halaman Persiapan tampil -- ditambah `'waspang.projects.persiapan'`).

**Revisi setelah testing awal (sesi lanjutan)**: Step 1 & sub-step-nya TIDAK LAGI mewajibkan metadata (EXIF) foto -- validasi EXIF dihapus (`show.blade.php` + `evidence-photo-grid.blade.php` + `kendala-modal.blade.php`), diganti auto-compress (`compressImage()`, resize max width 1280px, JPEG quality 0.75) di SEMUA jalur upload foto halaman ini (modal utama, upload-ulang eviden ditolak, foto kendala, foto BA KP). `exif-js` script dilepas dari halaman ini.

**BUG DITEMUKAN SAAT TESTING** (via baca `storage/logs/laravel.log` langsung, krn device_bash tidak bisa jalankan PHP/artisan -- lihat poin di bawah): `evidences.stage` ternyata kolom **ENUM legacy** (tabel `evidences` tidak py migration Schema::create(), diimport manual dari SQL lama, cuma izinkan 4 nilai lama) -- insert eviden stage BARU (`drm` dkk) gagal dgn "Data truncated for column 'stage'" walau validasi Laravel sudah dilebarkan sebelumnya (DB-nya sendiri belum ikut). **Fix**: migration baru `2026_09_08_091200_widen_evidences_stage_column.php` -- ganti kolom dari ENUM sempit jadi `VARCHAR(50)` bebas (supaya tidak perlu migration lagi tiap ada stage baru di masa depan).

**CATATAN HISTORIS — tindak lanjut migration ini sudah diselesaikan:**
- Migration `lop_kronologis`, kolom `perizinan_completed_at`/`stage_code`, dan pelebaran `evidences.stage` sudah tercatat sebagai **Ran** pada ledger live. Jangan mengikuti instruksi lama untuk menjalankan semua migration tanpa melihat daftar pending terkini di bagian 0.4.
- Reference screenshot desain yg dikirim user belum bisa saya lihat ulang secara visual dlm sesi ini (hilang dari konteks setelah compaction) -- tampilan dibangun murni dari spec tertulis; kemungkinan perlu penyesuaian detail visual setelah user lihat hasil nyata.
- FAB "Survey" bottom-nav (`route('surveyor.index')`, modul Site Survey/GIS milik SDI) sesuai instruksi user TETAP terpisah/tidak diubah.
- Kolom `unit_price`/`total_price` `BoqItem` tetap tidak fillable (bug lama, di luar scope) -- item BOQ hasil Survey baru (manual/import) otomatis TIDAK mengisi harga, sama seperti BOQ item lain di aplikasi.

---

## S. Revisi 2026-09-10 -- DRM dihapus dari alur aktif dan audit sumber status (STATUS ANTARA/HISTORIS)

Keputusan alur terbaru: Persiapan sekarang terdiri dari **Inisiasi → Survey → Perizinan → Material Delivery**. Finalisasi Survey langsung menulis `lops.status_progress = 'perizinan'`; route, controller action, accordion, upload baru, dan tombol selesai DRM sudah dihapus. Tombol **Update Kronologi** yang duplikat sebelum Finalisasi Survey juga dihapus karena tombol universal sudah tersedia.

Kode master `drm` **belum dihapus secara fisik**. Alasannya aman-data: nilai tersebut masih mungkin direferensikan oleh `lops.status_progress`, `lops.status_progress_before_hold`, dan histori tahap melalui foreign key. Untuk sementara kode itu disembunyikan dari daftar tahap aktif, tidak diterima untuk upload baru, dan pembacaan LOP lama dipresentasikan sebagai **Perizinan**. Data bukti/log DRM lama tetap disimpan sebagai histori.

Audit sumber status menghasilkan batas kanonik berikut:

1. Posisi alur LOP reguler: `lops.status_progress`.
2. Persetujuan SDI reguler saat ini: `projects.sdi_approval_status`.
3. Penanda Golive reguler saat ini: `projects.is_golive`.
4. Pada PT2, ketiganya memang sudah berada di `pt2_lops`: `status_progress`, `sdi_approval_status`, dan `is_golive`.

Pada titik audit ini kolom berikut **belum aman dihapus**; seluruh blocker ini kemudian diselesaikan pada bagian T:

- `projects.status_project` masih dipakai luas oleh dashboard, filter program, impor PID/BOQ, reminder, serta penanda drop/close/bast.
- `projects.status` masih dibaca oleh alur penutupan/Ready UT dan ekspor/impor.
- `projects.jenis_eksekusi` masih dipakai form/detail proyek; `execution_type` bukan status progres, melainkan jenis pelaksanaan bisnis.
- `lops.mapping_status` adalah status hasil pemetaan impor, bukan status pekerjaan.
- Kolom `status` pada evidence, survey, kendala, approval, import, GIS/CAD, BAUT, dan LACT adalah state milik domain masing-masing dan tidak boleh diganti dengan `status_progress`.
- `status_progress_before_hold` tetap diperlukan agar HOLD/DROP dapat kembali ke tahap sebelumnya.

**Status akhir catatan ini:** seluruh urutan aman tersebut sudah dijalankan pada bagian T — freeze dibuka, kolom LOP ditambah/backfill, pembaca/penulis dipindahkan, dashboard/import diverifikasi, DRM aktif dinormalisasi, dan kolom project legacy dihapus.

---

## T. Revisi 2026-09-10 -- Status proyek dikonsolidasikan penuh ke LOP (SELESAI)

Migration freeze sudah dibuka (`MIGRATIONS_FROZEN=false`; nilai default konfigurasi juga `false`). Eksekusi dilakukan dengan path migrasi spesifik agar dua migrasi lama yang masih pending tidak ikut berjalan tanpa audit.

### T.1 Audit dan rekonsiliasi data live

Kondisi sebelum konsolidasi:

- 1.308 project reguler dan 1.360 LOP reguler; **0 project tanpa LOP**.
- 7 project PT2 dan 297 LOP PT2; **0 project PT2 tanpa LOP**.
- Status Golive reguler lama: 2 project; persetujuan SDI approved: 2 project.
- Ada 17 project reguler dengan lebih dari satu LOP. Karena itu status tidak boleh lagi disimpan sebagai satu nilai agregat di parent project.

Migration `2026_09_10_120000_consolidate_regular_lop_statuses.php` menambahkan `sdi_approval_status`, `is_golive`, `golive_evidence_path`, dan `golive_at` ke `lops`, lalu menyalin data project lama ke setiap LOP. Nilai Golive/approved dinormalisasi ke `status_progress=golive`; project lama berstatus drop dipindahkan ke status LOP sambil menyimpan `status_progress_before_hold`. Nilai DRM historis pada posisi aktif dinormalisasi ke Perizinan.

Migration `2026_09_10_121000_drop_legacy_project_status_columns.php` melakukan rekonsiliasi terakhir, memverifikasi setiap project mempunyai LOP, lalu menghapus kolom agregat legacy berikut:

- `projects`: `status`, `status_project`, `sdi_approval_status`, `is_golive`, `golive_evidence_path`, `golive_at`.
- `pt2_projects`: `status`, `status_project`, `sdi_approval_status`, `is_golive`.

Migration kedua mempunyai `down()` yang dapat membangun ulang kolom parent dan mengagregasikan status dari LOP bila rollback darurat diperlukan.

Hasil pascamigrasi:

- Jumlah project/LOP tidak berubah dan orphan tetap 0.
- LOP reguler: 2 Golive + 2 SDI approved; cocok dengan nilai sumber sebelum migrasi.
- LOP PT2: 2 Golive + 2 SDI approved; status per LOP dipertahankan (tidak lagi ditimpa status agregat PID PT2).
- Sebaran LOP reguler: `drop=1`, `finishing=6`, `golive=2`, `inisiasi=1`, `instalasi=5`, `persiapan_instalasi=1344`, `survey=1`.
- Tidak ada lagi kolom status legacy pada kedua tabel project parent.

### T.2 Aplikasi yang disesuaikan

- Dashboard Admin/Super TIF/PM, matriks, KPI, detail matriks, dan filter sekarang membaca `lops.status_progress`/`lops.is_golive`. Filter Dashboard Admin menampilkan seluruh tahap aktif dari master `project_stages` (DRM tetap disembunyikan). Pengelompokan ringkas tetap 3 fase, tetapi pemetaannya kini benar: Instalasi+Pengukuran masuk Progress; Finishing+FI/OGP+Golive masuk Finish.
- Program OSP/NODE B/HEM/OLO/Konstruksi Eksternal, statistik project, tracking, reminder stale, dan halaman dashboard produksi tidak lagi membaca status project parent.
- Data PID/Data BOQ, modal edit, export Excel, template CSV, `PidImportService`, dan job impor lama menerima `status_progress` dan menulisnya ke LOP. Header `status_project` lama masih diterima hanya sebagai alias input kompatibilitas; tidak ada penulisan kembali ke parent.
- Filter PT2 (daftar, filter, matriks, import, dashboard) juga menggunakan status tiap `pt2_lops`, bukan parent `pt2_projects`.
- Baseline `database/schema/mysql-schema.sql` sudah diselaraskan dan ledger memuat kedua migrasi baru pada batch 32 dan 33.

### T.3 Verifikasi

- Lint seluruh PHP berubah: lulus.
- Kompilasi seluruh Blade: lulus.
- 24 test fokus / 94 assertion: lulus (`DatabaseSchemaBaseline`, `MigrationLedgerReconciler`, freeze guard, service Survey, dan workflow Survey).
- Smoke test render penuh terhadap Dashboard Admin dengan filter Survey, Data PID PT2 dengan filter Preparation, Program OSP dengan filter Survey, serta Dashboard PM: seluruhnya berhasil dirender setelah kolom parent dihapus.
- Suite test bawaan lain masih mempunyai kegagalan lama karena migrasi awal repository mencoba mengubah tabel `evidences` pada SQLite kosong; masalah fixture/baseline test tersebut tidak berasal dari konsolidasi status ini.
- Dua migration lama masih sengaja pending dan belum dieksekusi: performance indexes PM dashboard dan penghapusan enum role user. Keduanya tetap perlu audit terpisah.

---

Audit dan implementasi akan terus diperbarui pada dokumen ini selama refactor berlangsung.

## U. Stage 4e -- Deviasi nominal BOQ Survey vs BOQ Plan + Approval Redesign (SELESAI, sesi Claude ini)

Spesifikasi dari user: pada accordion Survey, tombol Finalisasi Survey menampilkan List BOQ Plan + volume (Material/Jasa dengan `pair_code` sama TETAP satu baris/satu volume, tidak dijumlahkan -- ini sebenarnya sudah jadi perilaku `SurveyPreparationService::groupBoqItems()` sejak Stage 4d, dikonfirmasi ulang saat audit sesi ini, tidak perlu diubah). Setelah volume BOQ Survey diinput, nilai nominalnya dibandingkan dengan BOQ Plan: deviasi >10% memunculkan accordion **Approval Redesign** (upload eviden foto/capture bukti persetujuan); deviasi <=10% langsung lanjut ke Perizinan seperti sebelumnya.

4 keputusan yang dikonfirmasi user sebelum eksekusi:
1. **Basis nominal**: nilai Rupiah (`quantity x harga designator per package`), bukan cuma volume.
2. **Granularitas**: dihitung agregat total 1 LOP (bukan per-item).
3. **Data harga tidak lengkap** (LOP belum punya `package_id`, atau ada designator tanpa baris `designator_package_prices` untuk package itu): **Selesai Survey diblokir total** dengan pesan error, waspang diarahkan pakai "Simpan Draf" sambil menunggu Admin melengkapi data.
4. **Approval Redesign bersifat self-declare** oleh Waspang sendiri (upload bukti foto/capture), TIDAK ada langkah approve terpisah oleh Admin/PM/role lain di sistem.

### U.1 Migration baru

`2026_09_10_130000_add_survey_deviation_fields_to_lops_table.php` (raw ALTER + guard `Schema::hasColumn`, gaya sama migration 090300/091100) -- tambah 2 kolom ke `lops`:
- `survey_deviation_percent` DECIMAL(8,2) NULL -- histori hasil hitung terakhir, murni catatan.
- `survey_redesign_required` TINYINT(1) NOT NULL DEFAULT 0 -- gate SEBENARNYA. Selama `true`, LOP tetap di `status_progress='survey'` walau volume Survey sudah final, sampai Waspang upload bukti persetujuan.

**BELUM DIJALANKAN** -- migration freeze sudah terbuka (`MIGRATIONS_FROZEN=false`), tapi eksekusi `php artisan migrate` tetap perlu dilakukan manual oleh user (device bridge sesi ini tidak punya akses ke binary PHP/artisan, sama seperti sesi-sesi sebelumnya).

### U.2 Logika penghitungan nominal

Method baru `SurveyPreparationService::evaluateNominalDeviation(Collection $groups, array $submittedVolumes, Collection $boqItems, ?int $packageId)`. Poin desain penting: 1 group (1 `pair_code`) tetap cuma pakai SATU volume (konsisten dgn `groupBoqItems()`), tapi nominalnya SENGAJA menjumlahkan harga Material DAN Jasa dalam pasangan itu (dua komponen biaya berbeda -- harga satuan material != harga satuan jasa pasang -- walau volumenya sama). Harga diambil dari `designator_package_prices` per `(designator_id, lop.package_id)` -- BUKAN dari `boq_items` (kolom `unit_price`/`total_price` di sana memang tidak fillable/tidak pernah diisi, bug lama yang sudah dicatat di bagian R).

Kalau `lop.package_id` null -> `missing` berisi penanda `__no_package__`. Kalau designator tertentu tidak punya baris harga utk package itu -> masuk daftar `missing` (kode designator). Kedua kasus ini memblokir `finishSurvey()` total (keputusan #3), pesan errornya beda (arahkan ke "lengkapi Package" vs "lengkapi harga designator X, Y, Z").

### U.3 `WaspangController::finishSurvey()` -- dirombak

Urutan baru: validasi volume -> hitung nominal (blokir kalau data harga tidak lengkap) -> hitung deviasi `|survey_total - plan_total| / plan_total` (kalau `plan_total=0`, deviasi dianggap 100% kalau survey_total>0, atau 0% kalau survey_total juga 0) -> baru masuk transaksi simpan volume. Kalau deviasi >10%: `survey_redesign_required=true`, `status_progress` TETAP `'survey'`, activity log `survey_deviation_detected`, flash `warning` (bukan `success`) menjelaskan angka deviasinya. Kalau <=10%: perilaku lama (status -> `'perizinan'`, log `survey_finalized`). Method ini aman dipanggil ulang -- kalau Waspang mengubah volume & submit lagi sebelum upload approval, deviasi dihitung ulang dari nol (bisa lolos otomatis kalau ternyata sekarang di bawah 10%).

### U.4 Method baru `WaspangController::uploadSurveyRedesignApproval()`

Endpoint terpisah (`POST /waspang/projects/{project}/survey/redesign-approval`, route `waspang.survey.redesign-approval.store`) -- guard: `status_progress` harus masih `'survey'` DAN `survey_redesign_required` harus `true`. Terima `photos[]` (wajib >=1, image, max 10MB/file, pola sama modal kendala), simpan ke `evidences/{lop-folder}/survey/redesign_approval/`, buat baris `Evidence` (`stage='survey'`, `evidence_type='redesign_approval'`) dengan **`status` langsung `'approved'`** (bukan `'pending'`) -- karena self-declare (keputusan #4), sengaja tidak dibuat masuk antrean Approval Eviden Admin. Setelah tersimpan: `survey_redesign_required=false`, `status_progress='perizinan'`, log `survey_redesign_approved`.

### U.5 View & UI

`resources/views/waspang/partials/survey-workflow.blade.php`: blok "Finalisasi Survey" lama (`@if($step['survey']['active'] && $surveyMapConfirmed)`) diubah jadi `@if(...&& $lop->survey_redesign_required) ... @elseif(...&& $surveyMapConfirmed) ... @endif` -- kalau sedang menunggu approval, accordion Finalisasi Survey (input volume) DIGANTI accordion baru "Approval Redesign Diperlukan" (kartu amber, tampilkan angka deviasi, form upload foto dengan kompresi client-side pola sama `kendala-modal.blade.php`/`compressImage()` global di `show.blade.php`). Begitu approval terkirim, `survey_redesign_required` balik `false` dan halaman otomatis pindah render ke accordion Perizinan (karena `status_progress` sudah berubah) di request berikutnya.

`resources/views/layouts/waspang.blade.php`: ditambah 1 blok flash message baru `@if(session('warning'))` (amber, sebelumnya layout cuma punya `success`/`error`) -- dipakai `finishSurvey()` saat deviasi >10% supaya pesannya benar-benar tampil ke Waspang (sebelum ini ditambah, `session('warning')` akan silently tidak muncul apa-apa).

### U.6 File yang diubah/dibuat (belum di-commit, menunggu review user)

- `database/migrations/2026_09_10_130000_add_survey_deviation_fields_to_lops_table.php` (baru)
- `app/Models/Lop.php` (+fillable `survey_deviation_percent`/`survey_redesign_required`, +cast)
- `app/Services/SurveyPreparationService.php` (+method `evaluateNominalDeviation()`, +import `DesignatorPackagePrice`)
- `app/Http/Controllers/WaspangController.php` (`finishSurvey()` dirombak, +method `uploadSurveyRedesignApproval()`, +const `SURVEY_DEVIATION_THRESHOLD`)
- `routes/web.php` (+1 route `waspang.survey.redesign-approval.store`)
- `resources/views/waspang/partials/survey-workflow.blade.php` (+accordion Approval Redesign, +JS kompresi foto)
- `resources/views/layouts/waspang.blade.php` (+flash `warning`)

### U.7 BELUM/PERLU TINDAK LANJUT USER

1. **Jalankan `php artisan migrate`** (1 migration baru: `2026_09_10_130000_...`) -- sesi ini tidak bisa menjalankannya sendiri (device bridge tidak punya akses ke binary PHP/artisan XAMPP).
2. Tidak bisa live-test dari sisi saya (browser/PHP tidak terjangkau) -- mohon testing manual: (a) LOP dengan `package_id` kosong -> pastikan Selesai Survey diblokir dgn pesan jelas; (b) LOP dengan package lengkap & deviasi <=10% -> pastikan langsung lanjut Perizinan seperti biasa (regresi check); (c) LOP dengan deviasi >10% -> pastikan accordion Approval Redesign muncul, angka deviasi masuk akal, upload foto berhasil & lanjut ke Perizinan; (d) ubah volume lagi sebelum upload approval (submit ulang Finalisasi Survey) -> pastikan deviasi dihitung ulang, bukan numpuk dari hitungan sebelumnya.
3. Arah deviasi saat ini dihitung **dua arah** (`abs()` -- survey lebih besar ATAU lebih kecil dari plan sama-sama bisa memicu Approval Redesign). User cuma menyebutkan "lebih dari 10% dari BOQ Plan" yang bisa dibaca 1 arah (survey > plan) -- kalau maksudnya cuma searah, tinggal hapus `abs()` di `WaspangController::finishSurvey()`, beri tahu saya.
4. Belum ada indikator visual "harga tidak lengkap" di UI Survey SEBELUM waspang klik Selesai Survey (baru ketahuan setelah submit & kena blokir) -- kalau mau preventif (badge peringatan dari awal accordion dibuka), ini pekerjaan tambahan terpisah.

---

## V. Insiden 10 Sept -- error `Unknown column 'status_project'` saat upload PID (BUKAN bug kode, worker belum di-restart)

User melaporkan upload PID gagal dgn `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'status_project' in 'field list'` saat insert ke `projects`, dan minta ditambahkan fallback "kalau status_project kosong, otomatis jadi inisiasi".

**Diagnosa**: dicek langsung `PidImportService::newRegularProjectPayload()` (baris 863) -- payload project baru SUDAH TIDAK menyertakan `status_project` sama sekali (konsisten dgn drop kolom di bagian T). Fallback yang diminta user JUGA SUDAH ADA di kode saat ini (baris ~657-685): `status_project` dibaca sbg alias input kalau `status_progress` kosong, lalu kalau hasilnya tetap tidak valid/kosong, otomatis jatuh ke `'inisiasi'` (via cek `in_array($statusProgress, $allowedStatuses)`). Jadi source code sesi ini sudah benar & sudah sesuai permintaan user -- **tidak ada perubahan kode yang dilakukan**.

Kesimpulan: error ini adalah **kelas masalah operasional yang sama persis dgn catatan di bagian M** -- upload PID diproses lewat queue (`ProcessPidImportJob`, `QUEUE_CONNECTION=database`), dan proses `php artisan queue:work` yg sedang berjalan kemungkinan besar masih meng-cache versi kode SEBELUM Stage T (saat `projects.status_project` masih ada & masih ditulis). Perubahan file tidak otomatis kepakai worker yang sudah berjalan sampai di-restart.

**Tindakan yang disarankan ke user**: jalankan `php artisan queue:restart` (atau matikan & jalankan ulang `php artisan queue:work` manual), lalu coba upload PID lagi. Kalau errornya masih persis sama setelah restart worker, baru perlu diaudit lebih lanjut (kemungkinan ada jalur import lain yang belum ditemukan).

---

## W. Revisi U -- Approval Redesign: foto bisa dihapus SEBELUM submit, terkunci SESUDAH submit

Permintaan user: pada form upload bukti Approval Redesign (bagian U.5), foto yang sudah dipilih harus bisa dihapus satu-satu sebelum diklik "Konfirmasi Disetujui & Lanjut ke Perizinan", tapi begitu sudah diklik (submit berhasil), foto itu tidak boleh bisa dihapus lagi.

**Sebelumnya**: file langsung ditaruh di `<input type="file">` via `DataTransfer` begitu dipilih -- tidak ada cara hapus 1 foto saja tanpa membuka ulang file picker dan memilih ulang semuanya.

**Sekarang**: `resources/views/waspang/partials/survey-workflow.blade.php` -- foto yang dipilih ditampung dulu di array JS (`surveyRedesignApprovalFiles`, pola sama dgn `selectedFiles` di `waspang/show.blade.php`), tiap thumbnail di preview dapat tombol × (`removeSurveyRedesignApprovalPhoto(index)`) yg cuma memanipulasi array + render ulang preview -- BELUM menyentuh `<input>` sama sekali. Input file yang sesungguhnya (dipakai form native submit, bukan AJAX) baru disusun dari isi array PERSIS sebelum submit (di listener `submit`, sebelum form benar-benar terkirim). Submit juga divalidasi client-side: kalau array kosong, submit dibatalkan (SweetAlert2, fallback native `alert()` kalau `Swal` belum ke-load).

Bagian "terkunci sesudah submit" tidak butuh perubahan tambahan -- itu otomatis terpenuhi karena tidak ada (dan tidak dibuat) endpoint hapus utk evidence `stage=survey, evidence_type=redesign_approval` sesudah tersimpan; begitu form ini submit, satu-satunya jalan mengubahnya adalah lewat DB langsung.

File yang diubah: `resources/views/waspang/partials/survey-workflow.blade.php` saja (fungsi JS di bagian U.5 diganti total, tidak ada perubahan controller/route/migration).

---

## X. Fitur "Re Survey" -- boq_survey_rounds/boq_survey_round_items, ronde histori tanpa duplikasi boq_items

Permintaan user: sebelum lanjut ke spec sub-step Perizinan, sub-step Survey butuh tombol **"Re Survey"** yang muncul setelah Survey selesai. Klik tombol ini membuka lagi UI Survey (termasuk daftar item BOQ) seperti kondisi awal, TAPI data BOQ Survey ronde SEBELUMNYA tidak boleh hilang/tertimpa -- tetap tersimpan di database dgn LOP yang sama, dibedakan per ronde (`boq_survey_1`, `boq_survey_2`, dst). Saat search LOP, hasilnya menampilkan BOQ Plan + tiap ronde BOQ Survey.

**4 keputusan user (ditanya via AskUserQuestion sebelum implementasi):**
1. Re Survey bisa dipicu **dari tahap manapun setelah Survey** (tidak dibatasi hanya saat LOP masih berada di stage 'survey') -- klik tombol MENGEMBALIKAN `status_progress` LOP ke `'survey'` lagi, apapun tahap LOP saat ini (Perizinan, Instalasi, dst).
2. Histori tiap ronde disimpan di **tabel baru** (`boq_survey_rounds` + `boq_survey_round_items`), BUKAN dgn menduplikasi baris `boq_items` -- supaya tidak mengganggu Dashboard PM, BAUT/LACT, evidence (`boq_item_id`), dan kalkulasi harga yang sudah bergantung pada `boq_items` sbg satu baris per designator per LOP (lihat riset di bawah).
3. Pengecekan deviasi nominal >10% (bag. U) **berlaku di SETIAP ronde**, termasuk ronde Re Survey ke-2, 3, dst -- bukan hanya round 1.
4. Tampilan "BOQ Plan + Survey round 1, 2, dst" ditaruh di **halaman Admin** (`admin.projects.show` / `resources/views/admin/project-detail.blade.php`), sbg riwayat read-only.

**Riset sebelum desain**: `boq_items.quantity_actual`/`quantity_plan` dipakai luas di luar Waspang -- `BautController`/`LactController` (generate dokumen BAUT/LACT baca `quantity_plan`), `DashboardController` (kalkulasi "Nilai Total" = harga × `quantity_plan`), `DashboardPmController` (banyak agregasi SQL grouped by `progress_category`, dgn komentar eksplisit soal risiko double-count kalau Material+Jasa `pair_code` sama), `ImportController`, `ProjectController`, `TeknisiPt2Controller`. Karena itu `boq_items` TETAP satu baris per designator per LOP seperti sekarang (tidak diduplikasi per ronde) -- histori ronde disimpan terpisah.

**Desain data (migration `2026_09_10_140000_create_boq_survey_rounds_tables.php`):**
- `boq_survey_rounds` -- 1 baris per ronde per LOP: `lop_id`, `round_number` (unique bareng lop_id), `status` (`in_progress`/`completed`), `plan_total`, `survey_total`, `deviation_percent`, `redesign_required`, `started_by`/`started_at`, `finished_by`/`finished_at`, `note`.
- `boq_survey_round_items` -- snapshot SELURUH baris `boq_items` LOP tsb pada saat ronde selesai (`boq_item_id`, `designator_id`, `designator`, `item_name`, `unit`, `quantity_plan`, `quantity_survey`) -- `boq_item_id` sengaja TANPA foreign key krn baris `boq_items` (khususnya item tambahan Survey) bisa saja dihapus tapi histori harus tetap utuh.
- Model baru: `App\Models\BoqSurveyRound` (relasi `items()`, `lop()`, scope `inProgress()`), `App\Models\BoqSurveyRoundItem`. `Lop::surveyRounds()` -- `hasMany` order by `round_number`.

**Alur (`app/Http/Controllers/WaspangController.php`):**
- `finishSurvey()` -- sebelum commit, ambil/buat ronde `in_progress` via `currentSurveyRound()` (auto-buat round 1 kalau LOP lama belum pernah punya ronde sama sekali -- backward compatibility). Simpan `plan_total`/`survey_total`/`deviation_percent`/`redesign_required` ke ronde ini. Kalau deviasi ≤10% -> `status_progress` langsung `'perizinan'` DAN ronde langsung `completeSurveyRound()` (snapshot semua `boq_items` LOP ke `boq_survey_round_items`, tandai `status='completed'`). Kalau >10% -> ronde TETAP `in_progress` (menunggu upload Approval Redesign), snapshot BELUM dibuat.
- `uploadSurveyRedesignApproval()` -- setelah evidence tersimpan & `status_progress` maju ke `'perizinan'`, baru panggil `completeSurveyRound()` utk ronde yg sama (snapshot dibuat di sini, bukan di `finishSurvey()`, supaya snapshot mencerminkan kondisi FINAL ronde tsb).
- `startReSurvey($project)` (route POST `waspang.survey.re-survey.start`) -- guard: minimal 1 ronde `completed` harus sudah ada (kalau belum pernah Survey sama sekali, ditolak), dan TIDAK BOLEH ada ronde `in_progress`/`survey_redesign_required` yg masih menggantung (harus diselesaikan dulu). Kalau lolos: buat `BoqSurveyRound` baru (`round_number` = max+1, `status='in_progress'`), lalu **reset** `lop->status_progress = 'survey'`, `survey_redesign_required = false`, `survey_deviation_percent = null`. `boq_items` TIDAK disentuh sama sekali (quantity_actual lama tetap ada sbg nilai awal form sampai waspang timpa via Selesai Survey ronde baru). Log activity `re_survey_started` (mencatat `status_before` sebelum direset).
- Helper privat baru: `currentSurveyRound(Lop $lop)`, `completeSurveyRound(BoqSurveyRound $round, Lop $lop)`.

**UI Waspang** (`resources/views/waspang/partials/survey-workflow.blade.php`): 2 blok baru ditambahkan di dalam accordion Survey, di LUAR gate `$step['survey']['active']` (supaya tetap tampil walau LOP sudah lanjut ke tahap lain, sesuai keputusan #1):
- "Riwayat BOQ Survey" -- daftar seluruh `$surveyRounds` (round number, status, tanggal selesai, badge % deviasi kalau ada).
- Tombol "Re Survey" -- muncul kalau `$canStartReSurvey` true (dihitung di `WaspangController::persiapan()`: ada ronde `completed`, tidak ada ronde `in_progress`, & `survey_redesign_required` false). Konfirmasi JS sebelum submit.

**UI Admin** (`resources/views/admin/project-detail.blade.php` + `DashboardController::show()` eager-load `lop.surveyRounds.items`): section baru "Riwayat BOQ Survey" di bawah BOQ Item -- 1 kartu per ronde (label "Round N — Survey Awal"/"Round N — Re Survey", status, badge deviasi) + tabel snapshot item (Designator/Item/Plan/Survey ronde tsb).

**Migration BELUM dijalankan** (sama seperti bag. U) -- user perlu `php artisan migrate` utk `2026_09_10_130000_...` dan `2026_09_10_140000_create_boq_survey_rounds_tables.php` sekaligus.

**Catatan lanjutan**: setelah fitur Survey (termasuk Re Survey) ini dianggap tuntas, user akan menjelaskan detail spec sub-step Perizinan berikutnya.

## Y. Fix bag. X -- tombol "Re Survey" tidak muncul utk LOP yang sudah lebih dulu selesai Survey sebelum fitur ini ada

User melaporkan: sudah migrate (`2026_09_10_130000_...` & `2026_09_10_140000_create_boq_survey_rounds_tables.php`), tapi tombol Re Survey tetap tidak muncul.

**Penyebab**: `$canStartReSurvey` (dihitung di `WaspangController::persiapan()`) & guard di `startReSurvey()` sama-sama mensyaratkan minimal 1 baris `boq_survey_rounds` berstatus `completed` utk LOP tsb. Baris itu HANYA dibuat oleh `finishSurvey()`/`uploadSurveyRedesignApproval()` -- keduanya cuma jalan saat LOP MASIH di tahap Survey. LOP yang Survey-nya sudah selesai SEBELUM kode bag. X ini dideploy (yaitu hampir semua LOP yang sedang berjalan saat ini) tidak akan pernah memicu kedua fungsi itu lagi, jadi tidak akan pernah punya baris ronde sama sekali -- `$surveyRounds` selalu kosong, tombol Re Survey tidak pernah muncul, permanen.

**Fix**: helper baru `WaspangController::ensureBaselineSurveyRound(Lop $lop)` -- idempotent (no-op kalau LOP sudah punya baris ronde apapun), dipanggil di awal `persiapan()` (supaya "sembuh sendiri" cukup lewat refresh halaman biasa) & juga di awal `startReSurvey()` (jaga-jaga). Kalau LOP belum punya baris ronde SAMA SEKALI tapi `status_progress`-nya sudah bukan `inisiasi`/`survey` lagi (artinya pernah Selesai Survey di masa lalu), backfill 1 baris `boq_survey_rounds` round 1 berstatus `completed` (`finished_at` diambil dari `perizinan_completed_at` kalau ada, fallback `updated_at`) + snapshot `boq_survey_round_items` dari `boq_items` SAAT INI (satu-satunya data yang tersedia -- volume Survey asli sebelum fitur ini sudah menimpa `quantity_actual`, tidak ada jalan mengambil nilai yang lebih lama).

File yang diubah: `app/Http/Controllers/WaspangController.php` saja (method baru + 2 titik pemanggilan). Tidak ada migration/route/blade tambahan.

## Z. Redesign accordion Perizinan -- "Add Perizinan" (kategori + kronologi + eviden opsional, bisa berkali-kali), tombol "Perizinan Selesai" langsung aksi

Permintaan user: sub-step Perizinan diubah desainnya -- ada tombol **"Add Perizinan"** yang tiap diklik menampilkan form: pilih kategori perizinan, teks kronologi, upload eviden foto/file BA KP (opsional). Bisa Add Perizinan berkali-kali. Tombol "Update Kronologi" yang lama DIGANTI oleh tombol **"Perizinan Selesai"** yang langsung mengubah status LOP ke Material Delivery (tanpa upload BA KP wajib terpisah lagi seperti sebelumnya).

**3 keputusan user (ditanya via AskUserQuestion sebelum implementasi):**
1. Tombol "Perizinan Selesai" **tetap wajib minimal 1x Add Perizinan** sudah tersimpan (menjaga aturan lama: setiap aktivitas perizinan wajib kronologi) -- BUKAN bebas tanpa syarat.
2. `lops.permit_category_id` **selalu ikut kategori TERBARU** yang dipilih di Add Perizinan manapun, tapi kategori tiap entri tetap tercatat utuh sbg histori di baris kronologinya masing-masing (tidak hilang/tertimpa).
3. Upload eviden di form Add Perizinan **boleh campur foto & PDF sekaligus** dalam 1 input file (tidak dipisah 2 form).

**Desain data (migration `2026_09_10_150000_add_perizinan_entry_columns.php`):**
- `lop_kronologis.permit_category_id` (baru, FK nullable ke `permit_categories`, `nullOnDelete`) -- kategori yang dipilih SAAT entri Add Perizinan itu dibuat, terpisah dari `lops.permit_category_id` yang mencerminkan kategori TERBARU.
- `evidences.lop_kronologi_id` (baru, FK nullable ke `lop_kronologis`, `nullOnDelete`, TANPA constraint arah sebaliknya) -- menautkan eviden opsional yg diupload bersamaan Add Perizinan ke baris kronologi tsb, supaya bisa ditampilkan menyatu per entri (bukan grid eviden terpisah seperti sebelumnya).
- Model: `LopKronologi` -- tambah relasi `permitCategory()` & `evidences()` (`hasMany(Evidence::class, 'lop_kronologi_id')`). `Evidence` -- tambah `lop_kronologi_id` ke `$fillable` + relasi `kronologi()`.

**Alur (`app/Http/Controllers/WaspangController.php`):**
- `addPerizinan(Request $request, $project)` (route POST `waspang.perizinan.add`, BARU) -- guard status LOP harus `perizinan`/`drm` (alias lama). Validasi: `permit_category_id` (required, exists), `event_date` (required, date), `note` (required, string max 2000), `files` (nullable array, tiap file `mimes:jpg,jpeg,png,pdf|max:10240`). Dalam transaksi: buat 1 baris `LopKronologi` (`stage_code='perizinan'`, simpan `permit_category_id` di baris itu), update `lop->permit_category_id` ke kategori TERBARU, lalu utk tiap file upload buat `Evidence` (`evidence_type` otomatis `ba_kp` kalau ekstensi pdf, else `eviden_perizinan`; `status='pending'`, `lop_kronologi_id` diisi id kronologi tsb) di folder `evidences/{lop}/perizinan/add_perizinan/`. Log activity `add_perizinan`.
- `togglePerizinanSelesai($project)` (route POST `waspang.perizinan.selesai`, DISEDERHANAKAN) -- request body TIDAK lagi divalidasi (tidak ada file wajib lagi, `Request $request` type-hint DIHAPUS dari signature krn tidak dipakai). Guard tetap sama: status LOP `perizinan`/`drm`, DAN minimal 1 baris `lop_kronologis` `stage_code='perizinan'` harus sudah ada (persis cek lama, otomatis terpenuhi begitu Add Perizinan pertama tersimpan). Kalau lolos: langsung `lop->update(['perizinan_completed_at' => now(), 'status_progress' => 'material_delivery'])`, log `stage_transition`.
- `updatePerizinanCategory()` (route lama `waspang.perizinan.category`) -- method & route DIBIARKAN ada (tidak dihapus, low-risk) tapi SUDAH TIDAK DIPANGGIL dari UI manapun lagi (sudah digantikan pilihan kategori di dalam form Add Perizinan).

**UI Waspang** (`resources/views/waspang/show.blade.php` + partial baru `resources/views/waspang/partials/perizinan-modal.blade.php`):
- Accordion Perizinan: dropdown kategori standalone + grid "Eviden Proses Perizinan" terpisah + list "Kronologi Perizinan" terpisah -- SEMUA DIGABUNG jadi 1 seksi "Riwayat Perizinan": 1 kartu per entri `lop_kronologis` (badge kategori, tanggal, catatan, nama pencatat) + `@include('waspang.partials.evidence-photo-grid', ['photos' => $k->evidences])` kalau entri itu punya eviden (reuse partial yang sudah ada, otomatis dapat fitur hapus/upload-ulang per foto tanpa kerja tambahan).
- Tombol "+ Add Perizinan" di header seksi membuka modal baru (`perizinanModal`, fungsi `openAddPerizinanModal()`/`closePerizinanModal()`) -- form: select kategori, input tanggal, textarea kronologi, 1 input file (`files[]`, `accept="image/*,application/pdf"`, multiple).
- JS baru `compressMixedFileInput(input)` di `perizinan-modal.blade.php` -- kompres tiap file BERTIPE GAMBAR di input (pakai `compressImage()` global yg sudah ada), file PDF dilewati apa adanya (BEDA dgn `compressFileInputPhotos()` lama yg langsung MEMBUANG file non-gambar dari FileList -- tidak bisa dipakai di sini krn input ini sengaja campur foto+PDF).
- Blok "Radio Perizinan Selesai" (checkbox + form upload BA KP wajib) DIHAPUS TOTAL, diganti kartu simpel: 1 tombol "Perizinan Selesai" yang disabled (abu-abu) selama `$perizinanKronologis` masih kosong, aktif (biru, klik langsung submit dgn `confirm()`) begitu minimal 1 Add Perizinan sudah ada -- tidak ada modal/upload tambahan lagi di titik ini.

**Migration BELUM dijalankan** -- user perlu `php artisan migrate` utk `2026_09_10_150000_add_perizinan_entry_columns.php` (sekaligus 2 migration sebelumnya di bag. U/X kalau belum, walau sudah dikonfirmasi user sudah migrate utk 130000 & 140000).

## AA. Fix penomoran & CTA "Lanjut Step" -- Step 1 Persiapan salah loncat langsung finish Persiapan Instalasi, penomoran Instalasi/Pengukuran/Finishing tidak sinkron dgn stepper

User melapor: setelah menyelesaikan Step 1 Persiapan (4 sub-step: Inisiasi/Survey/Perizinan/Material Delivery), tombol CTA paling bawah salah -- minta disesuaikan supaya lanjut ke "Step 2 Persiapan Instalasi" dgn benar, dan supaya penomoran step konsisten di SEMUA halaman.

**Sumber kebenaran penomoran** ternyata SUDAH ADA & SUDAH BENAR di `resources/views/waspang/partials/stepper.blade.php` (komponen global, di-include di atas setiap halaman step Waspang): **1 Persiapan -> 2 Persiapan Instalasi -> 3 Instalasi -> 4 Pengukuran -> 5 Finishing -> 6 Selesai**. Masalahnya, beberapa teks INLINE di halaman lain tidak ikut diupdate saat Persiapan Instalasi dipecah jadi halaman sendiri (Step 2) -- jadi tidak sinkron dgn stepper di atasnya:

1. **`resources/views/waspang/show.blade.php`** (Step 1 Persiapan) -- CTA final paling bawah, saat `lop->status_progress === 'persiapan_instalasi'` (artinya Step 1 baru selesai), SEBELUMNYA langsung **POST** ke `WaspangController::finishPersiapanInstalasi()` (route `waspang.persiapan-instalasi.finish`) dgn label salah **"Lanjut Step 2 - Instalasi"**. Padahal `finishPersiapanInstalasi()` itu SENDIRI mensyaratkan eviden Barang Tiba & Perizinan SUDAH terupload (upload-nya ada di halaman Step 2, yg belum pernah dibuka) -- jadi tombol ini kalau diklik cuma akan gagal dgn error "Upload Eviden Barang Tiba dan Eviden Perizinan terlebih dahulu" tanpa mengarahkan waspang kemanapun. **Fix**: diganti jadi LINK biasa (GET) ke halaman Step 2 (`route('waspang.projects.persiapan-instalasi', ...)`), label dibetulkan jadi **"Lanjut Step 2 - Persiapan Instalasi"**. Cabang `$seq > 6` (LOP sudah lewat Persiapan Instalasi) labelnya juga dibetulkan dari "Lihat Step 2 Instalasi" -> **"Lihat Step 3 Instalasi"**.
2. **`resources/views/waspang/steps/instalasi.blade.php`** -- header inline "Step 2 Instalasi" -> **"Step 3 Instalasi"**; CTA bawah "Next Step 3 - Pengukuran" -> **"Next Step 4 - Pengukuran"**.
3. **`resources/views/waspang/steps/pengukuran.blade.php`** -- header inline "Step 3 Pengukuran" -> **"Step 4 Pengukuran"**; CTA bawah "Next Step 4 - Finishing" -> **"Next Step 5 - Finishing"**.
4. **`resources/views/waspang/steps/finishing.blade.php`** -- header inline "Step 4 Finishing" -> **"Step 5 Finishing"** (CTA-nya sendiri, link ke `waspang.projects.review_final`, tidak menyebut nomor step jadi tidak perlu diubah).

`resources/views/waspang/steps/persiapan-instalasi.blade.php` TIDAK diubah -- labelnya ("Step 2 Persiapan Instalasi" di komentar, CTA "Lanjut Step 3 - Instalasi") sudah benar dari awal & sudah konsisten dgn stepper; halaman ini juga TIDAK pakai header inline `<h2>Step N ...</h2>` (murni mengandalkan `@include('waspang.partials.stepper')`). `resources/views/waspang/steps/persiapan.blade.php` (file lama, header masih bilang "Step 1 - Persiapan") dicek TIDAK dipanggil controller manapun (`persiapan()` render ke `waspang.show`, bukan `waspang.steps.persiapan`) -- dead file, dibiarkan apa adanya.

Semua perubahan murni teks/link blade (tidak ada migration/controller/route baru).

## AB. Fix bug nyata -- boq_items.quantity_actual dipakai dobel (Volume BOQ Survey vs qty aktual Instalasi Step 3), kolom baru `quantity_survey`

User melapor: di Step 3 Instalasi, kolom "Qty Aktual" sudah terisi seperti Volume BOQ Survey -- padahal Volume Survey beda dgn qty aktual, dan qty aktual seharusnya BARU diisi saat Waspang input progress di Step 3 (item yang ditampilkan di Step 3 adalah BOQ Plan).

**Akar masalah**: sejak fitur Survey nominal-deviation (bag. U) & Re Survey (bag. X) dibuat, `WaspangController::persistSurveyVolumes()` (Selesai/Simpan Draf Survey) dan `addSurveyBoqItem()` (tambah designator hasil Survey) SAMA-SAMA menulis Volume BOQ Survey ke kolom `boq_items.quantity_actual` -- kolom yang SAMA dipakai `uploadEvidence()` utk menyimpan qty aktual Instalasi Step 3 (blok "UPDATE QUANTITY ACTUAL & REASON"). Akibatnya begitu Survey selesai, `quantity_actual` sudah terisi Volume Survey walau Waspang belum pernah menyentuh Step 3 sama sekali -- Step 3 pun menampilkannya seolah2 progress sudah ada.

**Fix (migration `2026_09_10_160000_split_quantity_survey_from_quantity_actual.php`)**: kolom BARU `boq_items.quantity_survey` (INT NULL) jadi rumah KHUSUS Volume BOQ Survey. `quantity_actual` mulai sekarang MURNI dipakai Instalasi Step 3 saja.

**Perbaikan data lama** (dikonfirmasi user sebelum dijalankan, lihat AskUserQuestion): migration yg sama otomatis:
1. Isi `quantity_survey` dari snapshot RONDE TERAKHIR tiap item di `boq_survey_round_items` (bag. X) -- akurat krn snapshot itu diambil PERSIS saat Survey/Re Survey selesai, tidak peduli `quantity_actual` sudah/belum sempat ditimpa Instalasi.
2. Kosongkan (`NULL`) `quantity_actual` **HANYA** utk item yang `quantity_survey`-nya barusan terisi (pernah lewat Survey) **DAN** TIDAK PERNAH punya histori aktivitas `update_quantity_actual` di `project_activity_logs` (dicek via `JSON_EXTRACT(meta, '$.boq_item_id')`) -- artinya item yang SUDAH PERNAH diinput progress Instalasi asli (walau kebetulan sama dgn Volume Survey) **TIDAK DISENTUH SAMA SEKALI**, supaya data progres asli tidak pernah berisiko hilang.

**Perubahan kode:**
- `app/Models/BoqItem.php` -- tambah `quantity_survey` ke `$fillable`.
- `app/Services/SurveyPreparationService.php::groupBoqItems()` -- key hasil grouping diganti dari `'quantity_actual' => $actualItem->quantity_actual` jadi `'quantity_survey' => $actualItem->quantity_survey`.
- `resources/views/waspang/partials/survey-workflow.blade.php` -- draft-value fallback utk item tambahan (`is_additional`) baca `$group['quantity_survey']` (sebelumnya `$group['quantity_actual']`).
- `app/Http/Controllers/WaspangController.php`:
  - `addSurveyBoqItem()` -- designator tambahan hasil Survey disimpan ke `quantity_survey` (BUKAN lagi `quantity_actual`).
  - `persistSurveyVolumes()` (dipakai `saveSurveyBoqDraft()` & `finishSurvey()`) -- update `quantity_survey`.
  - `completeSurveyRound()` -- snapshot `boq_survey_round_items.quantity_survey` sekarang baca dari `$item->quantity_survey` (sudah fresh dari `persistSurveyVolumes()` di transaksi yg sama).
  - `ensureBaselineSurveyRound()` (backfill Re Survey utk LOP lama, bag. Y) -- fallback `$item->quantity_survey ?? $item->quantity_actual` (jaga2 utk LOP yg migration 160000 lewatkan krn saat itu belum py baris ronde), SEKALIGUS memindahkan nilainya permanen ke `quantity_survey` + kosongkan `quantity_actual` dgn pengecekan histori `update_quantity_actual` yg SAMA PERSIS dgn migration.

Tidak ada perubahan di `uploadEvidence()` (blok update qty aktual Instalasi) -- itu SUDAH BENAR dari awal, memang seharusnya murni utk Step 3.

**Migration BELUM dijalankan** -- user perlu `php artisan migrate` utk `2026_09_10_160000_split_quantity_survey_from_quantity_actual.php` (bergantung pada tabel `boq_survey_rounds`/`boq_survey_round_items` dari migration 140000 sudah ada -- SUDAH dikonfirmasi user migrate sebelumnya).

## AC. Fix -- item tambahan BOQ Survey ikut nongol/dihitung di checklist Step 3 Instalasi (harusnya cuma item BOQ Plan)

User menegaskan: yang ditampilkan di Step 3 Instalasi seharusnya item dari **BOQ PLAN** saja -- item hasil "Tambah Designator" di sub-step Survey (`WaspangController::addSurveyBoqItem()`, dibuat dgn `quantity_plan = null` krn memang tidak berasal dari Plan Admin) **TIDAK PERLU** ikut ditampilkan di Step 3.

**Penyebab**: seluruh filter "item material utk progress Instalasi" di codebase (`$materialBoqItems = $boqItems->filter(...)`) SELAMA INI hanya menyaring berdasarkan designator (`M-` prefix ATAU `type === 'material'`), TANPA mengecualikan item yang `quantity_plan`-nya `null`. Karena item tambahan Survey SERING berupa Material (prefix `M-`), item2 itu ikut lolos filter & muncul sbg baris checklist Instalasi -- padahal mereka bukan bagian BOQ Plan sama sekali.

**Fix**: tambahkan syarat `quantity_plan !== null` di SEMUA titik yang menghitung `$materialBoqItems` utk keperluan progress/checklist Instalasi (`app/Http/Controllers/WaspangController.php`):
1. `dashboard()` -- kartu ringkasan progress Instalasi per project.
2. Helper progress kedua (dipakai alur `isProjectReadyUt`).
3. `instalasi()` -- **halaman Step 3 Instalasi itu sendiri** (yang dilaporkan user).
4. `reviewFinal()` -- Step "Validasi Akhir" (perbandingan Plan vs Aktual, item tanpa Plan memang tidak relevan dibandingkan).
5. `isInstalasiApproved()` -- helper gate approval Instalasi.
6. `instalasiSubmittedComplete()` -- versi query DB (`BoqItem::where(...)`), ditambah `->whereNotNull('quantity_plan')`.

Kalau tidak seluruhnya dibetulkan sekaligus, `$boqTotal`/`$boqApproved` di berbagai tempat (dashboard, gate "Instalasi selesai") akan tetap terhitung dari angka yang salah (memasukkan item tanpa Plan) walau tampilan Step 3-nya sendiri sudah benar -- makanya semua titik dibetulkan bersamaan, bukan cuma `instalasi()`.

Tidak ada migration baru -- murni perubahan logic filter (tambah 1 syarat `quantity_plan !== null`) di 6 titik tsb.


## AD. Fix + Revisi -- Stepper checklist 2 warna (upload vs approved), sumber item BOQ Instalasi/Finishing ikut ronde Survey terbaru, & bug lanjutan filter Material di `Project::progressSummary()`/`finishing.blade.php`

User melaporkan Step 3 Instalasi di stepper global (`stepper.blade.php`) tidak pernah berubah jadi checklist (icon check) walau Waspang sudah selesai upload semua foto progress. Sekaligus minta revisi: item BOQ yang dipakai di Step 3 Instalasi & Step 5 Finishing seharusnya dari **ronde BOQ Survey TERBARU** (`boq_survey_rounds`/`boq_survey_round_items`, fitur Re Survey bag. X) kalau LOP itu punya ronde yang SELESAI, dan HANYA fallback ke BOQ Plan (`quantity_plan !== null`, bag. AC) kalau LOP belum pernah punya ronde Survey selesai sama sekali. Diminta juga: SETIAP step di stepper berubah jadi checklist begitu Waspang selesai upload (bukan cuma stelah admin approve).

**Root cause #1 (kenapa checklist tidak pernah muncul)**: checklist hijau di `stepper.blade.php` (`$stepNDone`) dihitung dari `$seq > N` (posisi `lops.status_progress` di 11-tahap `project_stages`) -- dan `status_progress` HANYA maju dari `instalasi` -> `pengukuran` (lalu `pengukuran` -> `finishing`) lewat 1 tempat: blok auto-transisi di `ProjectController` (bagian approve eviden, ~baris 787-825), yang di-gate `$summary['instalasiDone']`/`$summary['pengukuranDone']` dari `Project::progressSummary()` -- dan KEDUANYA itu mensyaratkan SEMUA eviden material **status approved**, bukan cuma ter-upload. Jadi sebagian ini MEMANG BY DESIGN (approval tetap syarat lanjut step, dikonfirmasi user) -- bukan murni bug.

**Root cause #2 (bug nyata, kelas sama dgn bag. AC tapi di lokasi ke-7 & ke-8 yang kelewat)**: 2 titik filter "item Material" TERNYATA belum kena fix bag. AC:
1. `Project::progressSummary()` -- loop pembentuk `$materialIds`/`$finishingRequiredIds` (dasar hitung `instalasiApproved`/`finishingApproved`, JUGA dipakai auto-transisi `status_progress` di atas) cuma filter prefix `M-`, TANPA `quantity_plan !== null`. Akibatnya: kalau LOP punya item tambahan Survey (Material, `quantity_plan = null`) yang TIDAK PERNAH tampil/diupload di Step 3 (sejak bag. AC), item itu tetap ikut kehitung di `materialTotal` -- `instalasiApproved` TIDAK PERNAH bisa capai `materialTotal` -- `instalasiDone` SELALU `false` -- `status_progress` macet permanen di `instalasi` walau semua item yang BENAR2 tampil ke Waspang sudah full di-approve admin. Ini kemungkinan besar akar masalah utama yang dilaporkan user.
2. `resources/views/waspang/steps/finishing.blade.php` -- filter `$materialBoqItems`-nya cuma cek `type === 'material'`, TANPA `quantity_plan !== null` SAMA SEKALI (beda dgn 6 titik lain yg sudah dibenahi bag. AC) -- item tambahan Survey ikut nyasar ke checklist Finishing.

**Keputusan user** (dikonfirmasi via pertanyaan klarifikasi sebelum implementasi):
1. Checklist stepper JADI 2 WARNA: **KUNING** = Waspang sudah selesai upload SEMUA foto wajib tahap itu (status eviden apapun -- pending/approved), murni indikator visual progres upload sendiri. **HIJAU** = sudah di-approve admin SEMUA (persis definisi lama, `$stepNDone`/`$seq`). Approval TETAP jadi syarat lanjut ke step berikutnya (status_progress LOP) -- TIDAK diubah/dilonggarkan.
2. Item BOQ Instalasi & Finishing: ronde BOQ Survey TERBARU yang SELESAI kalau ada (else BOQ Plan) -- DAN label "Plan" yang ditampilkan ke Waspang ikut brubah jadi `quantity_survey` ronde itu (bukan `quantity_plan` asli Admin lagi), karena itu revisi resmi hasil Survey lapangan.

**Implementasi**:
- `app/Models/Project.php`:
  - Method baru `materialProgressItems(): array` -- sumber tunggal (single source of truth) utk "item Material yang berlaku hari ini". Cari `BoqSurveyRound` `status=completed` ber-`round_number` terbesar milik LOP; kalau ada, ambil snapshot `boq_survey_round_items`-nya (filter M-/type material via `BoqSurveyRoundItem::boqItem` relation), lalu **clone** tiap `BoqItem` asli & timpa `quantity_plan`-nya dgn `quantity_survey` ronde itu (in-memory saja, TIDAK disimpan ke DB) -- `id_boq` & semua relasi eviden TETAP ke row asli. Kalau tidak ada ronde selesai, fallback filter lama bag. AC (`quantity_plan !== null` + M-/material). Return `['items' => Collection, 'source' => 'survey_round'|'plan', 'round' => ?BoqSurveyRound]`.
  - Method baru `stepUploadFlags(): array` -- versi "sudah upload" (bukan approved) dari `persiapanDone`/`instalasiDone`/`pengukuranDone`/`finishingDone`, dipakai stepper utk warna KUNING. `instalasiUploaded`/`finishingUploaded` dihitung dari `materialProgressItems()` (jadi otomatis ikut sumber ronde/plan yang sama).
  - `progressSummary()`: loop `$materialIds`/`$finishingRequiredIds` (dulu scan `$boqItems` mentah, cuma filter prefix `M-`) sekarang sumbernya `$this->materialProgressItems()['items']` -- **fix root cause #2.1** sekaligus menyamakan item acuan approval Instalasi/Finishing dgn item yang BENAR2 ditampilkan (ronde terbaru/plan), bukan lagi himpunan yang bisa beda sendiri.
- `app/Http/Controllers/WaspangController.php`:
  - `instalasi()` -- `$materialBoqItems` sekarang dari `$project->materialProgressItems()` (bukan filter manual lagi); tambah `$materialSourceType`/`$materialSourceRound` yg dilempar ke view utk badge sumber.
  - `isInstalasiApproved()` -- item acuan disamakan, pakai `$project->materialProgressItems()['items']` (dulu filter manual `quantity_plan !== null && M-` sendiri, bisa beda dgn `instalasi()` kalau ronde Survey ada).
- `resources/views/waspang/steps/finishing.blade.php` -- filter manual diganti panggil `$project->materialProgressItems()` juga (**fix root cause #2.2**, sekalian ikut revisi ronde terbaru); tambah badge sumber ("BOQ Survey Ronde N" / "BOQ Plan") di header list, sejajar dgn badge yg sama di `instalasi.blade.php`.
- `resources/views/waspang/steps/instalasi.blade.php` -- tambah badge sumber yang sama (variabel `$materialSourceType`/`$materialSourceRound` sudah dilempar dari controller). Loop item (`$project->boqItems`, hasil `setRelation()`) & hitung `$boqTotal`/`$boqUploaded`/`$instalasiUploadedComplete` di file ini SENDIRI TIDAK perlu diubah -- otomatis ikut karena sumbernya (`$project->boqItems`) sudah dari `materialProgressItems()`.
- `resources/views/waspang/partials/stepper.blade.php` -- tambah `$uploadFlags = $project->stepUploadFlags()` & `$stepNUploaded` (= `!$stepNDone && flag`) per step 1-5; tiap `$segments[N]` dapat key baru `'uploaded'`; `$circleClass`/`$labelClass`/icon check di 2 tempat (versi `<a>` & `<div>`) tambah 1 match-arm baru utk state kuning (`ring-2 ring-amber-400`, teks amber) sebelum fallback abu2 -- HIJAU (`done`) tetap dicek LEBIH DULU jadi kalau sudah approved ya langsung hijau, bukan kuning dulu.

**TIDAK diubah** (di luar scope eksplisit user, supaya blast radius kecil): `reviewFinal()` (Step "Validasi Akhir"/cetak UT) & `instalasiSubmittedComplete()` TETAP pakai BOQ Plan murni (bag. AC) apa adanya -- 2 halaman itu memang laporan/perbandingan Plan vs Aktual, bukan checklist upload, dan user tidak minta itu ikut berubah. Auto-transisi `status_progress` di `ProjectController` TIDAK disentuh -- otomatis ikut benar krn sumber `instalasiDone`/`finishingDone`-nya (`progressSummary()`) sudah dibetulkan di atas.

Tidak ada migration baru -- murni perubahan logic (model + controller + 3 view blade).


## AE. Fix -- Step 4 Pengukuran tidak pernah checklist kalau SEMUA item ditandai "Tidak Ada" (N/A)

User melaporkan: kalau Step 4 Pengukuran sudah "complete" (semua item sudah ditangani), stepper harus tetap checklist WALAU semua item ditandai "Tidak Ada" (N/A) -- bukan cuma via upload foto.

**Root cause**: `status_progress` LOP CUMA bisa maju dari `pengukuran` -> `finishing` lewat 1 jalur di `ProjectController` (blok auto-transisi saat admin approve eviden, ~baris 787-820), yang di-gate `$summary['pengukuranDone']`. Kalau Waspang menandai SEMUA 5 item Pengukuran (`LopMeasurementCheck::ITEMS` -- otdr, file_sor, opm, kedalaman, eviden_lainnya) sebagai N/A TANPA upload eviden apapun, tidak pernah ada eviden utk di-approve admin -- transisi itu TIDAK PERNAH kepicu, walau `pengukuranDone` (`LopMeasurementCheck::isDone()`, bag. sebelumnya "Opsi B") SUDAH `true`. Stepper (hijau, `$step4Done`) jadi macet permanen krn `status_progress` tidak pernah lewat sequence 8, meski secara definisi Pengukuran sudah tuntas.

Sekaligus dibenahi: flag KUNING (`stepUploadFlags()['pengukuranUploaded']`, bag. AD) sebelumnya cuma cek 3 dari 5 evidence_type (otdr/opm/kedalaman) & TIDAK menghitung penanda N/A sama sekali -- jadi kalau ada item ditandai N/A, kuning juga tidak pernah nyala.

**Fix**:
- `app/Http/Controllers/WaspangController.php::toggleMeasurementCheck()` -- setelah `$check->save()`, replikasi gate auto-transisi yang SAMA PERSIS dengan `ProjectController` (PT2 barrier, LOP hold/drop tidak disentuh, `currentSequence === 8`) lalu cek `$project->progressSummary()['pengukuranDone']` -- kalau true, `status_progress` LOP di-update ke `finishing`. Jadi walau tidak ada satupun eviden yang di-approve (semua item N/A), LOP tetap bisa maju begitu item terakhir ditandai N/A.
- `app/Models/Project.php::stepUploadFlags()` -- bagian Pengukuran ditulis ulang: loop semua `LopMeasurementCheck::ITEMS` (5 item, bukan 3), per item "sudah diisi" (kuning) = ada eviden APAPUN statusnya (dengan alias nama lama `file_sor`/`otdr_sor` & `eviden_lainnya`/`lainnya`, sama seperti `pengukuran()`/`toggleMeasurementCheck()`) ATAU baris `lop_measurement_checks`-nya `is_not_applicable = true`. Method jadi butuh `$lop` juga (`loadMissing(['evidences', 'lop'])`).

Tidak ada migration baru. Tidak menyentuh `progressSummary()`'s `pengukuranDone` (definisi hijau) -- itu sudah benar dari awal (`LopMeasurementCheck::isDone()`); yang kurang cuma PEMICU transisi `status_progress`-nya saat jalur "semua N/A, nol eviden" dan definisi flag kuning yang belum lengkap.


## AF. UI Approval Admin disesuaikan dgn refactor "flow 11-tahap" + dibangun alur FI-OGP Golive/Golive lengkap

User: "sekarang lanjut sesuaikan untuk UI approval pada admin sesuai dengan refactor yang kita sedang jalankan termasuk stepper nya sesuaikan dengan waspang termasuk FI-OGP Golive dan Golive sesuai status progress yang terbaru". 2 keputusan eksplisit lewat AskUserQuestion: (1) bangun ALUR LENGKAP FI-OGP Golive/Golive (bukan cuma kosmetik/tombol manual sementara), (2) benahi SEMUA tempat di Admin yang menampilkan warna/label stage (bukan cuma stepper evidence-approval).

**Root cause #1 (warna/label stage tidak konsisten & tidak lengkap)**: 3 file (`admin/projects/index.blade.php`, `admin/projects/partials/project-card.blade.php`, `admin/project-detail.blade.php`) masing-masing punya if/elseif SENDIRI yang string-match `$stageLabel` ('Finishing'/'Pengukuran'/'Instalasi') utk nentuin warna badge -- TIDAK PUNYA cabang utk 'FI-OGP Golive'/'Golive' sama sekali, jadi 2 tahap paling akhir (& paling positif) itu jatuh ke cabang "else" yang berarti merah (warna utk LOP bermasalah). `admin/projects/tracking.blade.php` juga scan BOQ Material-nya sendiri (`type==='material'` doang, tanpa `quantity_plan !== null`) -- beda dari `Project::materialProgressItems()` (bag. AC/AD) -- dan `$stageLabels`/`$stageProgress`-nya cuma sampai Finishing (seq 9), berhenti di situ.

**Root cause #2 (FI-OGP Golive/Golive "belum dikerjakan")**: `ProjectController::approveEvidence()` punya komentar eksplisit "Stage 5, belum dikerjakan" -- `status_progress` LOP tidak pernah maju otomatis lewat `finishing` (seq 9). Model `LopGoliveSubmission`/`LopGoliveVerification` + relasi `Lop::goliveSubmission()`/`goliveVerification()` SUDAH ADA (migration sudah jalan) tapi NOL controller/route/view yang memakainya -- benar2 dead code sampai sekarang.

**Implementasi**:

1. **`app/Models/Project.php`** -- method statis baru `stageColorClasses(?string $color): array`, 1 sumber warna Tailwind (accent/border/progress/badge/dot) per `project_stages.color` (slate/amber/blue/indigo/emerald/purple/green/orange/red + default abu2), dipetakan MANUAL per-warna (bukan interpolasi string) supaya tetap ke-scan Tailwind JIT. Dipanggil dari `progressSummary()['effectiveStageColor']` (hold->orange, drop->red di-override di pemanggil).

2. **Warna/label stage disamakan** di 4 tempat, semua baca `$summary['effectiveStageLabel']`/`stageColorClasses($summary['effectiveStageColor'])` (hold/drop-safe, konsisten dgn `progressSummary()`):
   - `admin/projects/index.blade.php` & `admin/projects/partials/project-card.blade.php` -- if/elseif lama diganti panggil `stageColorClasses()`.
   - `admin/project-detail.blade.php` -- "Status Progress" yang tadinya baca `$lop->stage?->name` mentah (bukan hold/drop-safe, tanpa warna) sekarang jadi badge berwarna dari `stageColorClasses()`.
   - `admin/projects/tracking.blade.php` -- 3 perubahan: (a) sumber item Material disamakan dgn Waspang via `$project->materialProgressItems()['items']`; (b) `$stageLabels`/`$stageRoutes`/`$stageProgress` ditambah 2 entry (`fi_ogp_golive` "FI-OGP Golive"/Step 5, `golive` "Golive"/Step 6), progress 2 tahap ini dari `effectiveStageSequence` (bukan ambang persentase kayak 4 tahap lama); (c) `$stageEvidenceStats` loop ditambah cabang baca `LopGoliveSubmission`/`LopGoliveVerification` (bukan tabel `evidences`) utk 2 tahap baru itu.

3. **Alur FI-OGP Golive/Golive dibangun lengkap** (bukan stopgap):
   - `app/Http/Controllers/ProjectController.php` -- 2 method baru: `reviewGolive($id)` (GET, tampilkan halaman submission) & `submitGoliveDocuments(Request, $id)` (POST, terima 4 file satu2/parsial, `updateOrCreate` ke `LopGoliveSubmission`, simpan ke `storage/app/public/evidences/golive/{lop_id}/...`; begitu ke-4 lengkap DAN `finishingDone` DAN persis `currentSequence===9` DAN bukan PT2/hold/drop/closed -> `status_progress` LOP di-set `'fi_ogp_golive'`, log via `ProjectActivityService`). Gate PERSIS sama pola dgn `approveEvidence()`/`toggleMeasurementCheck()` (bag. AE).
   - `app/Http/Controllers/SdiGoliveController.php` (BARU, terpisah dari `SdiController` yg cuma utk `Pt2Lop`) -- `index()` (list LOP reguler `status_progress='fi_ogp_golive'`), `show($id)` (lihat submission + form upload UIM), `verify(Request, $id)` (upload `capture_uim`, `updateOrCreate` `LopGoliveVerification`; begitu tersimpan DAN persis `currentSequence===10` DAN bukan PT2/hold/drop/closed -> `status_progress` LOP di-set `'golive'` + `lops.is_golive=true` + `golive_at=now()`, log via `ProjectActivityService`).
   - `routes/web.php` -- route baru `admin.evidences.review.golive` (GET) & `admin.evidences.golive.submit` (POST) di group `role:admin,superadmin,super_tif`; route baru `sdi.golive.index`/`sdi.golive.show`/`sdi.golive.verify` di group `role:sdi` (terpisah dari `sdi.golive.store` lama yg PT2-only).
   - View baru: `admin/evidences/review-golive.blade.php` (Step 5 = form upload 4 dokumen dgn preview link file tersimpan + toggle jenis input Mancore foto/excel; Step 6 = status verifikasi SDI read-only), `sdi/golive/index.blade.php` (daftar LOP nunggu verifikasi, extends `layouts.sdi` spt `sdi/index.blade.php`), `sdi/golive/show.blade.php` (detail submission + form upload capture UIM, tombol verify disabled/hidden kalau submission belum lengkap).

4. **`resources/views/admin/evidences/partials/stepper.blade.php`** -- REWRITE TOTAL: dari 4 segmen jadi 6 (Persiapan/Instalasi/Pengukuran/Finishing/FI-OGP Golive/Golive). Sebelumnya method ini recompute SENDIRI semua status "done" dari filter BOQ M-prefix mentah (beda dari `materialProgressItems()`) -- sekarang semua baca dari `$project->progressSummary()` (hijau=approved) & `$project->stepUploadFlags()` (kuning=uploaded blm approved, step 1-4) plus `LopGoliveSubmission`/`LopGoliveVerification` (step 5-6), 2-tone yang SAMA PERSIS dgn stepper Waspang (bag. AD) supaya kedua sisi selalu konsisten. Step 5 "done" = `effectiveStageSequence >= 11`; Step 6 "done" = `lops.is_golive` OR sequence >= 11.

**TIDAK diubah** (di luar scope eksplisit): `resources/views/admin/evidences/review-finishing.blade.php`'s `$stepSummary` (mini review card Persiapan/Instalasi/Pengukuran internal di halaman itu) & `$materialBoqItems` filter lokalnya sendiri -- keduanya duplikasi terpisah dari stepper utama, sengaja tidak disentuh supaya blast radius kecil (dicatat di summary sblmnya juga).

Tidak ada migration baru (tabel `lop_golive_submissions`/`lop_golive_verifications` sudah ada dari sebelumnya, cuma belum dipakai sama sekali). Semua validasi file baru (`kml`, `pdf`, `xls/xlsx`, `image/*`) memakai mimes list yg sudah ada presedennya di codebase (pola KML dari fitur lain, pola image dari `SdiController::submitGolive`).

---

## Section AG — Stepper Admin 2 Warna (Hijau/Kuning) + Fix "N/A" pada Step Ukur

**Tanggal**: 2026-09-11
**Diminta oleh user**: "untuk stepper pada admin jika sudah di approve maka checklist dan warna hijau jika belum warna kuning, kemudian untuk step ukur jika waspang memilih button tidak ada maka munculkan saja N/A atau tidak ada jangan pending"

### Root cause #1 — Stepper punya 3 warna (emerald/amber/blue), harusnya cuma 2

`resources/views/admin/evidences/partials/stepper.blade.php` versi sebelumnya (Section AF) memberi warna biru (blue) pada step yang sedang dibuka/dilihat admin (`$isCurrent`) meskipun step itu BELUM di-approve — jadi step yang belum disetujui tidak selalu tampil kuning, tergantung apakah admin sedang membukanya atau tidak. User minta aturan sederhana: **hijau + centang HANYA kalau sudah di-approve admin**, selain itu **selalu kuning**, apapun state-nya (belum upload / sudah upload tapi belum di-review / sedang dibuka).

**Fix**: Rewrite total logika warna di `stepper.blade.php` jadi 2 closure PHP:
- `$stepCircleClass(bool $done, bool $isCurrent)` → base color HANYA `bg-emerald-500` (done) atau `bg-amber-400` (belum), lalu highlight "sedang dibuka" diubah dari warna isian jadi RING (border) saja — supaya tetap ada indikasi visual "sedang di step ini" tanpa melanggar aturan 2-warna.
- `$stepLabelClass(bool $done)` → text emerald atau amber, dipakai konsisten di label bawah tiap step.

Diterapkan ke seluruh 6 step (Persiapan/Instalasi/Ukur/Finish/FI-OGP/Golive), sumber `$stepNDone` tetap dari `progressSummary()` (`persiapanDone`/`instalasiDone`/`pengukuranDone`/`finishingDone` — semua approval-gated) dan `effectiveStageSequence` untuk step 5 & 6 (FI-OGP Golive & Golive), tidak berubah dari Section AF. Variabel `$stepNUploaded` dan pemanggilan `stepUploadFlags()` dihapus dari file ini karena sudah tidak dipakai (state "sudah upload tapi belum approve" sekarang sama-sama kuning seperti "belum upload sama sekali").

Verifikasi balance: div 15/15, @php/@endphp 1/1, brace 75/75, paren 43/43.

### Root cause #2 — Step Ukur (Pengukuran) tampilkan "Pending" utk item yang ditandai "Tidak Ada" oleh Waspang

Waspang punya opsi menandai sebuah item pengukuran (OTDR / File SOR / OPM / Kedalaman / Eviden Lainnya) sebagai **"Tidak Ada"** (N/A) via kolom `is_not_applicable` di tabel `lop_measurement_checks` (model `LopMeasurementCheck`) — artinya item tsb memang tidak berlaku untuk LOP tersebut, BUKAN "belum sempat diisi". Namun UI review admin (`review-item.blade.php`, partial yang dipakai bersama oleh semua step review) tidak tahu apa-apa soal kolom ini — begitu `$items` (evidence untuk requirement itu) kosong, itu otomatis jatuh ke cabang default `'pending'`, jadi tampil badge "Pending" + pesan "Belum ada file/eviden diunggah" — padahal Waspang sudah eksplisit menandai item itu tidak berlaku.

**Fix — 2 file:**

1. **`resources/views/admin/evidences/review-pengukuran.blade.php`**: tambah query `$measurementChecksByKey` (dari `LopMeasurementCheck::where('lop_id', $project->lop->id_lop)->get()->keyBy('item_key')`) dan `$itemKeyMap` (memetakan 5 nilai `type` di `$requirements` — `otdr`, `otdr_sor`, `opm`, `kedalaman`, `lainnya` — ke key `LopMeasurementCheck::ITEMS` — `otdr`, `file_sor`, `opm`, `kedalaman`, `eviden_lainnya`; dikonfirmasi cocok persis dengan definisi `ITEMS` di model). Di loop `@foreach($requirements as $req)`, dihitung `$isNotApplicable` per item lalu dilempar ke partial via prop baru `'isNotApplicable' => $isNotApplicable`.

2. **`resources/views/admin/evidences/partials/review-item.blade.php`** (shared partial): tambah handling `$isNotApplicable` (default `false` kalau prop tidak dikirim — jadi step lain seperti Persiapan/Instalasi/Finishing yang tidak kirim prop ini tetap berjalan seperti biasa, tidak terdampak):
   - `$groupStatus` dapat cabang baru `'na'` (kalau `$total === 0 && $isNotApplicable`).
   - `$statusClass` dapat warna slate/abu utk status `'na'`.
   - `$statusLabel` baru (`'na' => 'Tidak Ada'`, selain itu tetap `ucfirst($groupStatus)`) — badge sekarang render `{{ $statusLabel }}` bukan `{{ ucfirst($groupStatus) }}` langsung.
   - `$iconText` dapat `'na' => '—'`.
   - Blok `@empty` (saat `$items` kosong) sekarang bercabang: kalau `$isNotApplicable` tampilkan ikon "—" + pesan `Ditandai "Tidak Ada" oleh Waspang -- item ini tidak berlaku untuk LOP ini.`; kalau tidak, tetap pesan lama "Belum ada file/eviden diunggah."

Verifikasi balance: `review-item.blade.php` → div 31/31, @if/@endif 8/8, @php/@endphp 3/3, brace 84/84, paren 55/55, @forelse/@endforelse 1/1. `review-pengukuran.blade.php` → div 8/8, @foreach/@endforeach 1/1, @php/@endphp 2/2, brace 22/22, paren 30/30.

### Scope — TIDAK diubah

- Step Persiapan/Instalasi/Finishing/FI-OGP tidak mengirim prop `isNotApplicable` ke `review-item.blade.php` — otomatis default `false`, behavior badge/pesan mereka identik dengan sebelumnya.
- Logika auto-advance status_progress, `progressSummary()`, `stepUploadFlags()`, dan seluruh alur FI-OGP Golive/Golive dari Section AF tidak disentuh.
- Warna/label stage di halaman lain (index, project-card, project-detail, tracking) dari Section AF tidak disentuh — perubahan Section AG ini murni di stepper approval partial dan review Step 3 Pengukuran.

### File yang diubah
- `resources/views/admin/evidences/partials/stepper.blade.php` (full rewrite v2)
- `resources/views/admin/evidences/partials/review-item.blade.php` (3 patch)
- `resources/views/admin/evidences/review-pengukuran.blade.php` (2 patch)

---

## Section AH — UI Menu "Approval Eviden" (Approval Konstruksi) Disesuaikan dengan Flow Terbaru

**Tanggal**: 2026-09-11
**Diminta oleh user**: "sesuaikan juga untuk UI di menu approval konstruksi sesuai dengan kondisi flow terbaru"

### Konteks

Menu ini adalah halaman inbox utama admin (`resources/views/admin/evidences/approval.blade.php`, route `admin.evidences.approval`, controller `ProjectController::approvalIndex()`) tempat admin melihat daftar LOP yang perlu di-review eviden-nya, dengan 3 tab: "Menunggu Review", "On Progress", "Selesai / Ready UT". Halaman ini TIDAK ikut disentuh saat Section AF membangun alur FI-OGP Golive/Golive dan menyamakan warna/label stage di 4 lokasi admin lain (index, project-card, project-detail, tracking) -- jadi halaman ini masih pakai logika lama yang tidak sadar akan 2 tahap baru tsb. Root cause & fix:

### Root cause #1 — Badge status per-baris salah baca "selesai review" setelah `progress` jadi berbasis 11-tahap

`Project::progressSummary()['progress']` (dari pekerjaan sebelumnya) dihitung dari `effectiveStageSequence` 1-11, jadi HANYA 100% persis di Golive (tahap 11). Sebelumnya badge "✓ READY UT" di kolom Status ditentukan oleh `$progressPercent >= 100` -- ini valid selama progress dihitung dari 4-step lama (Persiapan/Instalasi/Pengukuran/Finishing), tapi begitu progress ikut menghitung sampai ke FI-OGP Golive/Golive, LOP yang eviden-nya SUDAH TUNTAS (finishingDone) tapi baru sampai fi_ogp_golive (menunggu dokumen/verifikasi SDI, bukan eviden foto) progress-nya < 100% -- jadi badge salah tetap nampilkan "IN REVIEW" padahal kerjaan admin di menu approval eviden ini sudah beres.

**Fix**: badge status per-baris diganti dari biner "READY UT / IN REVIEW" jadi badge STAGE (label + warna) yang dibaca dari `progressSummary()['effectiveStageLabel']`/`['effectiveStageColor']` via `Project::stageColorClasses()` -- SAMA PERSIS dengan yang dipakai di stepper/tracking/index (Section AF/AG), supaya konsisten di semua lokasi. Ditambah indikator kecil terpisah "Perlu Review" (amber) yang HANYA muncul kalau `$pendingCount > 0` -- supaya sinyal "butuh aksi admin" (fokus utama halaman inbox ini) tidak hilang setelah badge utama diganti jadi penanda stage. Badge juga menangani state Hold ("HOLD · <label tahap sebelum hold>") dan Drop ("DROP").

Progress bar (angka %) juga diganti warnanya dari hardcode biru ke `$stageColors['progress']` (ikut warna stage saat ini) untuk konsistensi visual.

### Root cause #2 — Tab "Selesai / Ready UT" & "On Progress" tidak sadar hold/drop maupun FI-OGP Golive

Query lama:
- Tab "Selesai": `status_progress === 'golive'` PERSIS.
- Tab "On Progress": `status_progress NOT IN ('drop', 'golive')`.

Dua masalah: (1) LOP yang eviden-nya sudah tuntas tapi baru sampai `fi_ogp_golive` tidak pernah dianggap "Selesai" dari sisi approval eviden, padahal tidak ada lagi kerjaan review foto di situ untuk admin -- LOP ini malah nyangkut selamanya di tab "On Progress"; (2) LOP yang `status_progress`-nya berubah jadi `hold`/`drop` SETELAH sempat mencapai `fi_ogp_golive`/`golive` jadi HILANG dari SEMUA tab sekaligus (bukan match tab manapun), karena tab "On Progress" juga mengecualikan `drop`/`golive` secara string mentah tanpa melihat `status_progress_before_hold`.

**Fix** (`ProjectController::approvalIndex()`):
- Tab "Selesai": sekarang match kalau `status_progress` ATAU `status_progress_before_hold` (kolom yang menyimpan tahap sebelum LOP di-hold/drop) ada di `['fi_ogp_golive', 'golive']`, selama `status_progress` bukan `'drop'` murni -- plus syarat lama (tidak ada eviden pending/rejected) tetap dipertahankan.
- Tab "On Progress": mengecualikan LOP yang match kriteria "Selesai" di atas (baik langsung maupun via hold/drop dari tahap itu) dan bukan `'drop'` murni.

### Perubahan lain

- Tombol "Review" tiap baris sekarang diarahkan ke STEP yang sedang berjalan (dibaca dari `effectiveStageSequence`, hold/drop-safe: sequence 7→Instalasi, 8→Pengukuran, 9→Finishing, ≥10→Golive, default→Persiapan) -- bukan selalu balik ke Step 1 (Persiapan) seperti sebelumnya. Admin langsung mendarat di halaman review yang relevan.
- Mini badge progress per-step ditambah 1 (jadi 5): "FI-OGP: x/4" menghitung berapa dari 4 dokumen (capture valins, ABD, valid4/KML, mancore) yang sudah diupload lewat `$project->lop?->goliveSubmission`, supaya admin bisa lihat progres FI-OGP Golive tanpa buka halaman Review.

Verifikasi balance: `approval.blade.php` -> div 27/27, @if/@endif 4/4, @foreach/@endforeach 2/2, @forelse/@endforelse 1/1, @php/@endphp 2/2, brace 170/170, paren 148/148. `ProjectController.php` -> brace 161/161, paren 737/737 (utuh, hanya bertambah dari penyisipan blok baru).

### Scope — TIDAK diubah
- Perhitungan mini badge Persiapan/Instalasi/Pengukuran/Finishing (kolom "Progress Approval") tidak disentuh.
- Tab "Menunggu Review" (pending) tidak diubah -- sudah akurat (berbasis ada/tidaknya eviden `status=pending`).
- Method `approveEvidence`, `reviewGolive`, `submitGoliveDocuments`, dan seluruh alur FI-OGP Golive/Golive dari Section AF tidak disentuh.

### File yang diubah
- `app/Http/Controllers/ProjectController.php` (`approvalIndex()` -- tab "complete" & "active")
- `resources/views/admin/evidences/approval.blade.php` (badge status, progress bar color, mini badge FI-OGP, tombol Review)


---

## Section AI — Redesign Kolom Progress & Tab Filter Menu "Approval Eviden" (Simplifikasi + Semantik Tab Baru)

**Tanggal**: 2026-09-11
**Diminta oleh user**: "Progress approval tidak perlu angka 1/2 dll hanya tampilkan persentase dan status progress approval sudah sampai mana. buatkan tampilan filter yang modern, clean dan user friendly untuk Menunggu Review, On progress, Selesai menunggu review adalah LOP yang benar benar belum di lakukan approve, kemudian On progress LOP yang sudah ada approve, Selesai adalah untuk LOP yang sudah menyelesaikan sampai tahap FI - OGP Golive"

### Konteks

Lanjutan langsung dari Section AH (halaman `admin.evidences.approval`, `resources/views/admin/evidences/approval.blade.php` + `ProjectController::approvalIndex()`). Section AH sudah memperbaiki badge stage & query "Selesai"/"On Progress" agar sadar FI-OGP Golive dan hold/drop, tapi user merasa kolom "Progress Approval" masih terlalu ramai (5 mini badge angka per-step: Persiapan x/2, Instalasi x/2, Pengukuran x/3, Finishing x/2, FI-OGP x/4) dan tab filter masih polos (link teks biasa, tanpa indikator jumlah). User juga memberi definisi PERSIS untuk 3 tab yang berbeda dari implementasi Section AH:
- **Menunggu Review**: LOP yang **benar-benar belum pernah** di-approve (bukan sekadar "ada eviden pending").
- **On Progress**: LOP yang **sudah ada minimal 1 eviden approved**, tapi belum "Selesai".
- **Selesai**: LOP yang sudah mencapai tahap **FI-OGP Golive** (atau lebih, yaitu Golive) -- sama seperti definisi Section AH, dipertahankan.

### Perubahan 1 — Kolom "Progress Approval" disederhanakan (Controller: tidak berubah; View: simplifikasi @php + markup)

Dihapus semua 5 mini badge angka per-step (Persiapan/Instalasi/Pengukuran/Finishing/FI-OGP) beserta variabel `@php` pendukungnya yang sudah tidak dipakai (`$persiapanTotal`, `$persiapanApproved`, `$materialBoqItems`, `$instalasiTotal`, `$instalasiApproved`, `$hasOtdr`, `$hasOpm`, `$hasDalam`, `$pengukuranApprovedCount`, `$pengukuranTotal`, `$pengukuranApproved`, `$finishingRequiredItems`, `$finishingTotal`, `$finishingApproved`, `$goliveSubmission`, `$goliveDocsCount`). Kolom sekarang hanya menampilkan: label stage saat ini (dot berwarna + `effectiveStageLabel`, hold/drop-safe, sama sumber dengan badge status di kolom kanan) dan progress bar + angka persentase (`progressSummary()['progress']`, basis sequence 11-tahap sama seperti Section AH/AF). Jauh lebih ringkas dan tidak membingungkan admin yang sebelumnya harus menafsirkan banyak angka x/y sekaligus.

### Perubahan 2 — Tab filter didesain ulang jadi pill-switcher modern

Tab lama berupa 3 link teks polos dengan garis bawah aktif. Diganti jadi pill-switcher segmented control: 1 container rounded-2xl abu-abu (`bg-slate-100`/`bg-slate-800` dark) berisi 3 pill (`<a>`) yang masing-masing punya dot warna (amber/blue/emerald sesuai makna tab), label, dan badge count bulat kecil yang menampilkan jumlah LOP di tab tsb (`$tabCounts['pending'|'active'|'complete']`) -- badge count disembunyikan otomatis saat sedang search (`$tabCounts === null`, karena count per-tab tidak relevan/tidak dihitung saat search aktif, sesuai desain Section AI controller). Pill yang aktif mendapat background putih + shadow + warna teks sesuai tema tab; pill nonaktif abu-abu netral dengan hover state. Responsive: pill melebar penuh (flex-1) di layar sempit (mobile), auto-width di layar ≥sm.

### Perubahan 3 — Semantik 3 tab didefinisikan ulang sesuai instruksi eksplisit user (Controller: `ProjectController::approvalIndex()`)

Query lama Section AH untuk tab "Menunggu Review" (pending) hanya mensyaratkan LOP punya eviden dengan `status=pending` -- ini SALAH menurut definisi baru user (LOP yang sudah pernah di-approve sebagian tapi masih ada 1 pending eviden baru akan tetap masuk "Menunggu Review" secara keliru, padahal seharusnya "On Progress"). Diperbaiki total jadi 3 filter closure yang **saling eksklusif** dan konsisten dipakai baik untuk query utama maupun untuk hitung count tiap tab:

- **Pending** (`Menunggu Review`): `whereDoesntHave('evidences', status=approved)` -- LOP yang **belum pernah sama sekali** punya eviden approved (nol approval, apa pun status eviden lain: pending/rejected/belum ada eviden).
- **Active** (`On Progress`): `whereHas('evidences', status=approved)` **DAN** LOP terkait belum mencapai tahap "Selesai" (lihat definisi complete di bawah) -- yaitu `status_progress` (atau, kalau LOP sedang hold/drop, `status_progress_before_hold`) TIDAK termasuk `['fi_ogp_golive', 'golive', 'drop']`.
- **Complete** (`Selesai`): LOP terkait mencapai `fi_ogp_golive`/`golive` (baik langsung di `status_progress`, atau via `status_progress_before_hold` kalau sedang hold/drop dari tahap itu), `status_progress` bukan `'drop'` murni, DAN tidak ada eviden `pending`/`rejected` tersisa -- definisi ini identik dengan Section AH, hanya diformalkan ulang sebagai closure `$applyCompleteFilter` yang dipakai bersama oleh query utama & count.

Ketiga closure (`$applyPendingFilter`, `$applyActiveFilter`, `$applyCompleteFilter`) dipakai 2x: sekali di query ter-clone untuk menghitung `$tabCounts` (dengan filter Kawalanku/Program/Branch yang SAMA seperti query utama, supaya count akurat sesuai konteks filter admin saat ini), dan sekali lagi di query utama sesuai `status_filter` yang aktif. Saat mode search aktif, tab count tidak dihitung (`$tabCounts = null`, view otomatis sembunyikan badge count) karena hasil search lintas-tab tidak relevan dengan pembagian 3 tab ini.

Filter Kawalanku (`myKawal`), Program, dan Branch dipindah agar diterapkan SEBELUM filtering tab (baik untuk count maupun query utama), supaya angka count di tiap pill selalu konsisten dengan project yang sedang di-filter admin, bukan total keseluruhan sistem.

Verifikasi balance: `ProjectController.php` -> brace 164/164, paren 748/748. `approval.blade.php` -> div 26/26, @if/@endif 7/7, @foreach/@endforeach 2/2, @forelse/@endforelse 1/1, @php/@endphp 2/2, brace 148/148, paren 86/86, td 6/6. Tidak ada sisa referensi ke variabel yang sudah dihapus (`$persiapanTotal`, `$goliveDocsCount`, dst) di seluruh file.

### Scope — TIDAK diubah
- Badge status stage per-baris (kolom "Status & Aksi") dan tombol "Review" (routing per-step) dari Section AH tidak disentuh strukturnya, hanya ikut memakai `$effectiveLabel`/`$stageColors` yang sama seperti sebelumnya.
- Kolom "Eviden" (hitung Pending/Approved/Rejected mentah) tidak diubah.
- Logika `progressSummary()`, alur FI-OGP Golive/Golive, dan halaman lain (index, tracking, project-detail, stepper) dari Section AF/AG/AH tidak disentuh.
- Definisi tab "Selesai" (harus sudah capai fi_ogp_golive/golive, hold/drop-safe, tanpa eviden pending/rejected tersisa) TIDAK berubah dari Section AH -- hanya diformalkan ulang sebagai closure yang reusable untuk count.

### File yang diubah
- `app/Http/Controllers/ProjectController.php` (`approvalIndex()` -- restrukturisasi penuh: 3 closure filter, tab counts, filter Kawalanku/Program/Branch dipindah lebih awal)
- `resources/views/admin/evidences/approval.blade.php` (tab filter redesign pill-switcher + count badge, simplifikasi kolom Progress jadi label stage + persentase saja, pembersihan variabel `@php` yang tidak terpakai)
