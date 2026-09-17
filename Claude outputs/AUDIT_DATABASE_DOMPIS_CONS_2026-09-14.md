# Audit Database DOMPIS CONS — Dump 14 September 2026

Status dokumen: **hasil audit dan rancangan keputusan; bukan skrip eksekusi**  
Tanggal audit: 14 September 2026 (Asia/Jakarta)  
Database aktif yang dibaca: `dompis_cons` pada MariaDB `10.4.28`  

## 1. Ringkasan keputusan

Dump `dompis_cons-new.sql` **belum aman dijadikan sumber tunggal untuk membuat database baru**. Dump tersebut memang memuat fitur baru yang valid, tetapi juga membawa kembali kolom status induk yang sudah dihapus, mengurangi struktur `import_logs`, mencampur tiga collation, dan dibuat oleh MySQL 8.0.45 sedangkan database aplikasi aktif berjalan di MariaDB 10.4.28.

Urutan sumber kebenaran yang direkomendasikan adalah:

1. keputusan bisnis yang sudah disepakati di `ANALISA_REFACTOR_PERSIAPAN.md`;
2. migration Laravel yang sudah berstatus `Ran`;
3. metadata database aktif untuk validasi kondisi saat ini;
4. dump terbaru hanya sebagai bahan perbandingan, bukan untuk langsung di-import;
5. CDM/PDM target sebagai kontrak sebelum migration/database baru dibuat.

Tidak ada DDL, migration, update, delete, atau import data yang dijalankan selama audit ini.

## 2. Sumber yang dibandingkan

| Sumber | Waktu/versi | Ukuran | SHA-256 | Fungsi dalam audit |
|---|---:|---:|---|---|
| `/Users/hendrisyahputra/Documents/dompis_cons.sql` | 9 Sep 2026, MariaDB 10.4.28 | 69.526 byte | `2f947e0c371f73fc15d42e520a6fc134ef260ebac631ea33e490f0264bf451b2` | dump lama |
| `/Users/hendrisyahputra/Downloads/dompis_cons-new.sql` | 14 Sep 2026, MySQL 8.0.45 | 78.685 byte | `15614c7453788195fe97aa69420de0053fd7d86a2f7cb5116ae7f2a5e104fe87` | dump terbaru dari user |
| `database/schema/mysql-schema.sql` | baseline audit 9 Sep 2026 | 83.273 byte | `e68c5d171016ca4396277d37c8bd452ae03a779e5be55a2322752490ad4cf333` | baseline repo; kini perlu diregenerasi |
| `Claude outputs/ANALISA_REFACTOR_PERSIAPAN.md` | histori audit/refactor | 339.868 byte saat audit | `83ae807958f0eab11acc6493669163c6a32ed6230607b56c8432b35f2f1701a3` | keputusan dan kronologi perubahan |

Catatan: kedua dump SQL hanya berisi struktur; tidak ada `INSERT INTO`. Angka kualitas data di bawah berasal dari kueri baca-saja ke database aplikasi aktif, bukan dari isi dump.

## 3. Perubahan dump lama ke dump terbaru

### 3.1 Perubahan yang sesuai kebutuhan bisnis

| Perubahan | Penilaian |
|---|---|
| Tabel `approvals` dihapus | Sesuai. Approval aktif sudah berada pada status eviden, SDI, dan verifikasi Golive yang lebih spesifik. Pastikan tidak ada pemanggilan legacy sebelum drop pada database target. |
| Tambah `boq_survey_rounds` | Sesuai untuk menyimpan satu header per ronde Survey/Re-Survey per LOP. |
| Tambah `boq_survey_round_items` | Sesuai untuk snapshot BOQ tiap ronde agar histori lama tidak berubah saat redesign. |
| Tambah `boq_items.quantity_survey` | Sesuai sebagai proyeksi hasil survey terbaru; `quantity_actual` tetap khusus realisasi instalasi. |
| Tambah `lops.survey_deviation_percent` dan `survey_redesign_required` | Sesuai untuk gate deviasi/redesign. |
| Tambah `evidences.lop_kronologi_id` dan `lop_kronologis.permit_category_id` | Sesuai untuk kronologi perizinan beserta evidennya. |
| Tambah array file dan `fi_completed_at` pada `lop_golive_submissions` | Sesuai kebutuhan upload multi-file dan tanggal FI. |

Secara jumlah, dump lama berisi 54 tabel dan dump baru 55 tabel: satu tabel lama hilang (`approvals`) dan dua tabel survei baru ditambahkan.

### 3.2 Regresi atau ketidaksinkronan

| Area | Dump terbaru | Database aktif/migration | Dampak |
|---|---|---|---|
| Status reguler | `projects.status`, `status_project`, `sdi_approval_status`, `is_golive`, bukti/tanggal Golive muncul kembali | keenam kolom sudah dihapus oleh migration `2026_09_10_121000` batch 33 | Dua sumber status kembali terbentuk; dashboard/filter dapat berbeda. |
| Status PT2 | empat status kembali ada di `pt2_projects` | sudah dihapus oleh migration yang sama | Regresi sumber status PT2. |
| `import_logs` | hanya 12 kolom | database aktif memiliki 22 kolom | Job/import legacy dapat gagal karena sepuluh kolom hilang. |
| Baseline repo | belum memuat dua tabel ronde dan masih memuat `approvals` | database aktif sudah sampai batch 39 | `schema:load` tidak mereproduksi skema aktif. |

Sepuluh kolom `import_logs` yang hilang dari dump terbaru adalah `valid_rows`, `invalid_rows`, `errors`, `project_imported`, `project_updated`, `lop_imported`, `lop_updated`, `progress`, `started_at`, dan `finished_at`. Kode `ImportPidJob` masih menulis bagian dari kolom legacy ini, sehingga struktur aktif tidak boleh diperkecil tanpa refactor pemanggil terlebih dahulu.

## 4. Temuan audit terprioritas

### P0 — blocker sebelum database target dibuat

1. **Engine belum diputuskan.** Dump baru dibuat oleh MySQL 8.0.45, sedangkan aplikasi aktif memakai MariaDB 10.4.28. Collation `utf8mb4_0900_ai_ci` tidak tersedia di MariaDB 10.4. Import langsung berisiko gagal.
2. **Dump baru mengembalikan status ganda.** Sumber resmi harus tetap `lops.status_progress`, `lops.sdi_approval_status`, dan `lops.is_golive` (serta padanan pada `pt2_lops`).
3. **Struktur `import_logs` regresif.** Dump baru tidak kompatibel dengan database aktif dan jalur import lama.
4. **Data yatim sudah ada pada database aktif.** Foreign key baru tidak boleh dipasang sebelum rekonsiliasi; menyalakan `FOREIGN_KEY_CHECKS=0` untuk melewatinya bukan penyelesaian.

### P1 — wajib diselesaikan dalam desain dan rekonsiliasi

1. **Kardinalitas Survey salah untuk realitas multi-LOP.** `site_surveys` hanya memiliki `project_id`, sementara terdapat 17 proyek yang mempunyai lebih dari satu LOP. Survey, nama LOP, peta desain terbaru, dan histori desain harus terikat langsung ke `lop_id`.
2. **Relasi model masih ambigu.** `Project::lop()` adalah `hasOne`, padahal database mengizinkan satu proyek memiliki banyak LOP. `Project::lops()` sudah ada, tetapi seluruh consumer perlu dimigrasikan bertahap.
3. **Foreign key penting belum ada.** Contoh: `lops.project_id`, sebagian besar relasi `boq_items`, `evidences`, `project_activity_logs`, `project_issues`, `pro_assign`, master harga, notifikasi, dan beberapa relasi PT2 hanya memiliki index atau bahkan tidak memiliki keduanya.
4. **Histori tahap belum lengkap.** Hanya 9 baris histori untuk 2 LOP; 1.360 dari 1.362 LOP tidak memiliki histori. Laporan durasi hanya valid untuk transisi yang terjadi setelah instrumentasi aktif kecuali dilakukan backfill berlabel estimasi.
5. **Tipe angka tidak konsisten.** Harga/nilai reguler masih `varchar`, kuantitas reguler `int`, snapshot ronde `decimal(18,2)`, sedangkan PT2 memakai `double`. Ini berisiko menghasilkan pembulatan dan sorting numerik yang salah.
6. **Master designator belum unik.** Ditemukan 176 kelompok duplikat berdasarkan `(customer_id, designator)`. Unique constraint baru harus didahului mapping survivor dan relink seluruh child.
7. **Collation bercampur.** Dump baru memakai `utf8mb4_unicode_ci`, `utf8mb4_general_ci`, dan `utf8mb4_0900_ai_ci`. Dua tabel (`project_activity_logs`, `project_issues`) bahkan tidak menyatakan engine/collation secara eksplisit pada `CREATE TABLE`.
8. **Duplikasi foreign key semantik.** Banyak child menyimpan `project_id` sekaligus `lop_id`. Ditemukan 18 `project_activity_logs` dengan pasangan project–LOP yang tidak konsisten. Untuk data operasional per LOP, `lop_id` harus kanonik dan `project_id` diturunkan melalui LOP atau divalidasi ketat.

### P2 — utang teknis terkontrol

1. `users.role` enum dan `users.role_id` masih hidup bersamaan karena migration drop role enum masih pending. Target final menggunakan `role_id`.
2. `evidence_files` hanya memiliki ID dan timestamp, tanpa relasi/file path; putuskan untuk diremodel sebagai attachment atau dihapus setelah bukti pemakaian nol.
3. File Golive disimpan sekaligus sebagai kolom tunggal dan array JSON. Target sebaiknya memakai tabel dokumen ter-normalisasi agar multi-file, tipe, uploader, checksum, dan histori dapat diaudit.
4. Struktur reguler dan PT2 sangat duplikatif. Penggabungan penuh tidak disarankan dalam refactor ini; pertahankan bounded context PT2 pada PDM v1 dan evaluasi unifikasi pada ADR terpisah.

## 5. Profil database aktif per 14 September 2026

### 5.1 Volume dan status

| Metrik | Nilai |
|---|---:|
| Projects reguler | 1.310 |
| LOP reguler | 1.362 |
| Projects PT2 | 7 |
| LOP PT2 | 297 |
| BOQ reguler | 19.115 |
| Ronde BOQ Survey | 5, semuanya `completed` |
| Snapshot item ronde | 16 |
| LOP `inisiasi` | 1.283 |
| LOP `persiapan_instalasi` | 59 |
| LOP `instalasi` | 10 |
| LOP `finishing` | 7 |
| LOP `golive` | 2 |
| LOP `drop` | 1 |

Tidak ditemukan `status_progress` yang tidak ada pada `project_stages`. Dua LOP Golive konsisten antara `status_progress='golive'`, `sdi_approval_status`, dan `is_golive`.

### 5.2 Anomali integritas

| Pemeriksaan | Jumlah |
|---|---:|
| LOP menunjuk project yang tidak ada | 4 |
| BOQ menunjuk project yang tidak ada | 42 |
| Eviden menunjuk project yang tidak ada | 6 |
| Kendala menunjuk project yang tidak ada | 5 |
| Activity log menunjuk project yang tidak ada | 55 |
| Activity log dengan pasangan project–LOP tidak cocok | 18 |
| Project tanpa LOP | 0 |
| Project dengan lebih dari satu LOP | 17 |
| Duplikat `(customer_id, pid_sap)` pada project | 1 kelompok |
| Duplikat `(customer_id, designator)` | 176 kelompok |
| LOP tanpa package | 412 |
| LOP tanpa histori tahap | 1.360 |
| Ronde completed tanpa `finished_at` atau `finished_by` lengkap | 1 |

Contoh LOP yatim yang teridentifikasi: ID 530→project 525, ID 1388→1369, ID 1389→1370, dan ID 1390→1371. Audit tidak menghapus atau memperbaiki baris tersebut karena keputusan survivor/parent yang benar harus berasal dari pemilik data.

## 6. Keputusan desain target

| ID | Keputusan |
|---|---|
| D-01 | LOP adalah aggregate root proses lapangan dan sumber tunggal status operasional. |
| D-02 | Project adalah header/portofolio; status project diturunkan dari seluruh LOP, bukan disimpan sebagai kolom baru. |
| D-03 | Satu Project dapat memiliki banyak LOP; semua fitur baru wajib memakai relasi plural. |
| D-04 | Satu LOP dapat memiliki banyak versi Site Survey. Satu versi dapat ditandai current; versi lama immutable sebagai historical. |
| D-05 | `boq_items` menyimpan current projection: plan, survey terbaru, dan actual instalasi. `boq_survey_round_items` adalah snapshot immutable per ronde. |
| D-06 | Material/Jasa dengan `pair_code` sama digabung pada tampilan/input, tetapi tetap dua item biaya bila harga keduanya berbeda. |
| D-07 | Semua uang memakai `DECIMAL(18,2)` dan semua volume memakai `DECIMAL(18,3)` kecuali hitungan unit yang benar-benar integer. Jangan gunakan `FLOAT/DOUBLE` untuk uang. |
| D-08 | Charset target `utf8mb4` dengan satu collation yang didukung engine terpilih. Profil portabel sementara: `utf8mb4_unicode_ci`. |
| D-09 | File multi-upload disimpan sebagai baris attachment/document, bukan kombinasi kolom tunggal dan array JSON. |
| D-10 | Pembuatan database dilakukan dari migration/baseline terverifikasi, bukan mengimpor dump phpMyAdmin lintas-engine. |

## 7. Urutan remediasi yang aman

1. Setujui engine target dan CDM/PDM.
2. Bekukan perubahan struktur hanya pada jendela cutover; pengembangan migration tetap melalui review.
3. Buat laporan survivor untuk empat project hilang, satu PID SAP duplikat, dan 176 kelompok designator duplikat.
4. Rekonsiliasi child ke parent yang benar; jangan hard-delete sebelum backup dan sign-off.
5. Tambah `lop_id` serta metadata versi pada Site Survey; backfill berdasarkan aturan deterministik dan daftar exception.
6. Normalisasi tipe uang/volume menggunakan kolom bayangan, validasi parsing, lalu switch aplikasi.
7. Tambah foreign key satu per satu setelah setiap orphan check bernilai nol.
8. Jalankan rehearsal pada shadow database dan bandingkan count, checksum agregat, status distribution, serta alur Survey–Golive.
9. Setelah UAT dan rollback rehearsal lulus, buat baseline baru dari engine target.

## 8. Acceptance criteria audit

Database target baru boleh dibuat/cutover setelah seluruh kondisi berikut terpenuhi:

- keputusan engine tercatat dan seluruh DDL kompatibel;
- tidak ada status proses di `projects`/`pt2_projects`;
- `import_logs` dan pemanggilnya mempunyai kontrak yang sama;
- orphan untuk relasi yang akan diberi foreign key bernilai nol;
- setiap Site Survey operasional mempunyai `lop_id` yang valid;
- tidak ada lebih dari satu versi Survey current per LOP;
- tidak ada duplikat master pada business key yang telah disetujui;
- seluruh uang/volume berhasil dikonversi tanpa nilai tak-terbaca;
- smoke test Survey, Re-Survey, BOQ, instalasi, eviden, SDI, dan Golive lulus;
- rollback telah diuji pada shadow/staging, bukan hanya ditulis di dokumen.

## 9. Artefak terkait

- `SDLC_REFACTOR_DATABASE_DOMPIS_CONS.md` — tahapan, gate, pengujian, cutover, dan rollback.
- `CDM_PDM_DOMPIS_CONS_TARGET.md` — model konseptual dan model fisik target.
- `ANALISA_REFACTOR_PERSIAPAN.md` — histori keputusan/refactor aplikasi sebelumnya.
