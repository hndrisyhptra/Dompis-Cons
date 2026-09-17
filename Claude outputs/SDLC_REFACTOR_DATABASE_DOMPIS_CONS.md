# SDLC Refactor Database DOMPIS CONS

Versi: 1.0 — 14 September 2026  
Status: **rancangan pelaksanaan; database target belum dibuat**

## 1. Tujuan

SDLC ini mengatur perubahan dari skema berjalan menuju skema target yang:

- menjadikan LOP sebagai sumber tunggal status dan proses lapangan;
- mendukung Survey/Re-Survey dengan histori peta dan BOQ yang tidak tertimpa;
- menjaga data produksi selama refactor besar;
- dapat diuji, dipulihkan, dan diaudit;
- menghindari import langsung dump MySQL ke MariaDB atau sebaliknya.

## 2. Prinsip kerja

1. **Design first.** CDM dan PDM disetujui sebelum DDL/migration dibuat.
2. **Migration forward-only.** Migration yang pernah berjalan tidak diedit; koreksi dibuat dalam migration baru.
3. **Expand–migrate–contract.** Tambah struktur baru, backfill dan dual-read sementara, switch aplikasi, baru hapus struktur lama.
4. **No silent loss.** Semua merge, delete, dan konversi mempunyai laporan before/after dan daftar exception.
5. **LOP is authoritative.** Status Project hanya hasil agregasi dari LOP.
6. **Historical is immutable.** Peta, ronde BOQ, dan revisi eviden lama tidak ditimpa.
7. **Rollback must be rehearsed.** Backup saja tidak cukup; prosedur restore dan rollback aplikasi harus pernah diuji.

## 3. Siklus pelaksanaan dan quality gate

| Tahap | Aktivitas utama | Deliverable | Exit criteria/gate |
|---|---|---|---|
| 0. Governance | Tetapkan sponsor, data owner, engine target, maintenance window, RTO/RPO | ADR engine; RACI; kalender cutover | Engine dan owner exception disetujui |
| 1. Discovery | Audit dump lama/baru, migration, model, query, data aktif, integrasi | Audit database; inventory tabel/consumer | Semua sumber status dan critical consumer terpetakan |
| 2. Requirement baseline | Bekukan aturan bisnis Survey, BOQ, Redesign, status, SDI, Golive, PT2 | Business rules + acceptance criteria | Product owner menandatangani requirement |
| 3. CDM | Tetapkan entitas, aggregate root, relasi, ownership, histori | CDM disetujui | Tidak ada relasi/kardinalitas ambigu |
| 4. PDM | Tetapkan tabel, tipe, PK/FK, index, unique key, retention | PDM + data dictionary + ADR | DBA dan developer menyetujui PDM |
| 5. Data remediation | Profil orphan/duplikat, pilih survivor, relink child, tandai exception | Mapping cleanup; reconciliation report | Seluruh FK candidate bersih atau exception disetujui |
| 6. Migration engineering | Tulis migration expand, backfill idempotent, feature flag, observability | Migration set; rollback/runbook | Review kode dan dry-run lulus |
| 7. Shadow rehearsal | Clone tersanitasi, jalankan seluruh migration, validasi count/checksum | Rehearsal report | Zero unexpected delta; waktu cutover terukur |
| 8. Test & UAT | Unit, integration, data contract, performance, security, UAT role | Test evidence; UAT sign-off | Seluruh test P0/P1 lulus |
| 9. Cutover | Backup, stop writer, final sync, migrate, smoke test, buka trafik | Cutover log | Smoke test dan reconciliation lulus |
| 10. Hypercare | Monitor error, slow query, queue, data mismatch; siaga rollback | Hypercare dashboard/report | Stabil selama periode yang disepakati |
| 11. Contract & baseline | Hapus kolom legacy setelah masa aman; regenerate baseline | Clean schema baseline; updated docs | Tidak ada consumer legacy dan restore drill lulus |

## 4. Keputusan yang wajib dibuat pada Gate 0

### 4.1 Engine database

Saat ini terdapat dua lingkungan berbeda:

- database aplikasi lokal: MariaDB 10.4.28;
- dump terbaru: MySQL 8.0.45.

Pilih salah satu secara eksplisit:

| Opsi | Kelebihan | Konsekuensi |
|---|---|---|
| MariaDB target | Perubahan operasional paling kecil terhadap aplikasi aktif | Jangan gunakan `utf8mb4_0900_ai_ci`; sebaiknya naik ke rilis MariaDB LTS sebelum produksi baru |
| MySQL 8 target | Selaras dengan dump baru dan native JSON MySQL | Wajib compatibility test semua migration/raw SQL dan driver; ini migrasi engine, bukan sekadar restore |

Sebelum keputusan dibuat, PDM menggunakan profil portabel: InnoDB, `utf8mb4`, `utf8mb4_unicode_ci`, `DECIMAL`, dan fitur SQL yang tersedia pada kedua engine.

### 4.2 Availability dan recovery

Tetapkan nilai berikut sebelum implementasi:

- RPO: kehilangan data maksimum yang dapat diterima;
- RTO: waktu pemulihan maksimum;
- maintenance window;
- periode read-only/stop writer;
- lama hypercare;
- siapa yang berhak memutuskan rollback.

## 5. Workstream implementasi

### WS-1 — Rekonsiliasi sumber kebenaran

- pertahankan status hanya pada `lops` dan `pt2_lops`;
- hapus referensi baca/tulis status induk;
- tetapkan `Project::lops()` sebagai relasi resmi;
- ganti penggunaan `Project::lop()` yang ambigu per halaman/fitur;
- buat agregasi status Project sebagai query/read model.

### WS-2 — Survey dan historical design

- tambah kepemilikan `lop_id` pada Site Survey;
- tambah `revision_no`, `parent_survey_id`, `source_type`, dan penanda current;
- pastikan hanya satu Survey current per LOP;
- Redesign membuat revisi baru, tidak mengubah versi completed lama;
- peta accordion membaca revisi current, tetapi menyediakan histori;
- nama Survey/LOP diturunkan dari LOP yang dipilih, bukan input bebas.

### WS-3 — BOQ Plan, Survey, dan Actual

- `quantity_plan`: baseline admin;
- `quantity_survey`: proyeksi hasil survey terbaru;
- `quantity_actual`: realisasi instalasi;
- setiap finalisasi membuat `boq_survey_rounds` dan snapshot item;
- item tambahan Survey memiliki plan `NULL` dan survey wajib diisi;
- grouping M/J berdasarkan `pair_code` berlaku pada UI; biaya tetap menghitung komponen Material dan Jasa secara terpisah;
- uang menggunakan `DECIMAL`, bukan `varchar`/`double`.

### WS-4 — Eviden, kronologi, dan Golive

- seluruh eviden operasional memiliki `lop_id` yang valid;
- attachment multi-file menjadi baris terpisah dengan kategori, path, checksum, uploader, dan waktu;
- kronologi terkait stage dan permit category;
- approval/rejection tersimpan sebagai audit trail, bukan overwrite tanpa histori;
- `is_golive` dan `golive_at` hanya berubah melalui transaksi verifikasi SDI.

### WS-5 — PT2

- pertahankan tabel PT2 pada PDM v1 untuk mengurangi blast radius;
- selaraskan tipe, status, FK, index, dan aturan audit dengan reguler;
- kaji unifikasi reguler/PT2 sebagai program berikutnya melalui ADR, bukan dilakukan diam-diam dalam migration ini.

### WS-6 — Import, integrasi, dan baseline

- `import_processes` menjadi proses import kanonik;
- `import_logs` ditetapkan sebagai ringkasan kompatibilitas atau dipensiunkan setelah seluruh consumer pindah;
- error per baris tetap di `import_processes_errors`;
- baseline dihasilkan ulang hanya setelah PDM final dan seluruh migration lulus;
- dump phpMyAdmin tidak dijadikan deployment artifact.

## 6. Strategi migration: expand–migrate–contract

### Release A — Expand

- tambah kolom/tabel baru nullable;
- tambah index non-unique yang diperlukan untuk backfill;
- jangan drop kolom lama;
- deploy kode yang dapat membaca struktur lama dan baru;
- pasang metric mismatch.

### Release B — Migrate

- jalankan backfill dalam chunk kecil dan idempotent;
- simpan checkpoint dan jumlah processed/success/error;
- hasilkan tabel/file reconciliation;
- selesaikan exception dengan persetujuan data owner;
- aktifkan dual-write hanya bila benar-benar diperlukan dan berjangka pendek.

### Release C — Switch

- aktifkan feature flag pembacaan sumber baru;
- hentikan writer legacy;
- ukur mismatch minimal satu siklus operasional;
- pasang FK/unique constraint setelah data bersih.

### Release D — Contract

- hapus kolom/status/tabel legacy;
- hapus dual-write dan fallback;
- regenerate schema baseline;
- perbarui ERD, runbook, dan data dictionary.

## 7. Strategi lingkungan

| Lingkungan | Data | Tujuan |
|---|---|---|
| Local/dev | fixture sintetis | unit test dan pengembangan migration |
| Shadow | clone produksi tersanitasi | rehearsal migration dan profiling penuh |
| Staging | data representatif + integrasi sandbox | UAT role dan performance |
| Production | data aktual | cutover setelah seluruh gate lulus |

Database shadow harus memiliki engine, versi, collation, SQL mode, timezone, dan konfigurasi index yang sama dengan target produksi.

## 8. Test plan wajib

### 8.1 Schema dan data contract

- migration dari baseline kosong sampai head;
- migration dari clone skema lama sampai head;
- PK/FK/unique/index sama dengan PDM;
- nilai `NULL`, default, enum/check constraint sesuai kontrak;
- tidak ada collation/unsigned mismatch pada FK.

### 8.2 Rekonsiliasi data

- row count per tabel before/after;
- orphan count seluruh FK candidate = 0;
- duplicate count seluruh business key = 0;
- distribusi `status_progress`, SDI, dan Golive tidak berubah tanpa mapping resmi;
- total BOQ plan/survey/actual serta nilai rupiah cocok per LOP;
- file path dan checksum attachment tetap dapat diakses;
- setiap versi Survey current mempunyai histori parent yang valid.

### 8.3 Skenario fungsional prioritas

1. Admin input PID dan BOQ Plan serta upload KML.
2. Waspang membuka accordion Survey dan melihat peta baseline.
3. Tombol **Sesuai** melanjutkan ke finalisasi tanpa membuat redesign palsu.
4. Tombol **Redesign** membuka halaman tagging/rute dengan LOP yang benar.
5. Simpan draft tidak menghilangkan input.
6. Selesai Survey membuat ronde dan snapshot immutable.
7. Re-Survey membuat revisi baru dan peta lama tetap tersedia.
8. Pair M/J tampil satu baris input tetapi nominal menjumlahkan kedua harga.
9. Deviasi di atas ambang memicu gate redesign yang tepat.
10. Actual instalasi tidak menimpa volume Survey.
11. Eviden perizinan terhubung ke kronologi yang benar.
12. Upload FI multi-file, verifikasi SDI, dan Golive memperbarui satu LOP secara atomik.
13. Dashboard, filter, export, dan laporan memakai sumber status LOP.
14. Alur PT2 tetap lulus tanpa membaca status induk.

### 8.4 Non-functional

- bandingkan p95 query dashboard sebelum/sesudah;
- `EXPLAIN` untuk filter status, program, branch, dan timeline;
- test concurrency dua finalisasi Survey pada LOP yang sama;
- test idempotensi retry job/import;
- validasi authorization per role dan akses file;
- uji restore backup dan rollback release.

## 9. Acceptance threshold

| Area | Ambang lulus |
|---|---|
| Orphan | 0 pada seluruh FK target |
| Duplikat business key | 0, kecuali exception terdokumentasi yang belum diberi unique constraint |
| Data loss | 0 baris tanpa disposition |
| Status mismatch | 0 antara hasil mapping dan LOP |
| Survey current | tepat 0 atau 1 per LOP; tidak pernah >1 |
| Snapshot ronde | seluruh ronde completed memiliki item, finisher, dan waktu selesai |
| Parsing uang/volume | 100% berhasil atau masuk exception queue; tidak boleh diam-diam menjadi 0 |
| Test P0/P1 | 100% lulus |
| Rollback rehearsal | berhasil dalam RTO yang disetujui |

## 10. Cutover runbook ringkas

### T-7 sampai T-1 hari

- lock scope dan migration set;
- selesaikan UAT dan rehearsal final;
- verifikasi kapasitas backup dan disk;
- komunikasikan maintenance window;
- catat baseline metric dan checksum.

### Saat cutover

1. Aktifkan maintenance/read-only dan hentikan queue writer.
2. Pastikan job aktif selesai atau dicatat untuk retry.
3. Ambil backup konsisten dan uji dapat dibaca.
4. Jalankan preflight orphan/duplicate/status checks.
5. Jalankan migration sesuai runbook, bukan dump manual.
6. Jalankan post-migration reconciliation.
7. Jalankan smoke test skenario P0.
8. Buka trafik bertahap dan hidupkan queue.
9. Catat waktu, hasil, dan deviation.

### Trigger rollback

Rollback dipilih bila salah satu terjadi:

- migration gagal dan tidak dapat dilanjutkan secara idempotent;
- data loss/mapping tidak terjelaskan;
- status atau BOQ berbeda dari reconciliation;
- alur login, Survey, eviden, atau Golive P0 gagal;
- error rate/latency melewati ambang yang disetujui.

Rollback aplikasi dan database harus dipasangkan. Migration yang mengubah data secara irreversible tidak boleh mengandalkan `down()`; gunakan restore/snapshot atau compensating migration yang sudah diuji.

## 11. RACI minimum

| Aktivitas | Product Owner | Data Owner | Developer | DBA/Infra | QA/UAT |
|---|---|---|---|---|---|
| Aturan bisnis/status | A | C | R | C | C |
| Survivor orphan/duplikat | C | A/R | C | C | I |
| CDM/PDM | A | C | R | R | C |
| Migration/backfill | I | C | R | A/R | C |
| Rehearsal/performance | I | I | R | R | A/R |
| UAT | A | C | C | I | R |
| Go/no-go | A | A | C | C | C |
| Rollback | A | C | R | R | I |

Keterangan: R = Responsible, A = Accountable, C = Consulted, I = Informed.

## 12. Deliverable akhir

- audit database dan disposition setiap temuan;
- CDM/PDM disetujui;
- ADR engine dan ADR reguler/PT2;
- migration + backfill + feature flag;
- data reconciliation report;
- test evidence dan UAT sign-off;
- cutover/rollback runbook;
- baseline schema baru;
- monitoring dan hypercare report;
- dokumentasi operasional yang diperbarui.

