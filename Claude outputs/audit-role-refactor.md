# Audit & Analisis — Refactor Role User (ENUM → Tabel `roles`)
**Project:** dompis-cons | **Tanggal audit:** 2026-09-05 | **Status:** Phase 1 — Audit only, belum ada perubahan kode/DB

---

## Ringkasan Eksekutif

Sistem role saat ini murni berbasis **string literal** (`users.role`), dicek inline di 13 controller, 1 command, dan ~20 file Blade — **tidak ada satu pun** layer middleware/Gate/Policy yang memusatkan logic otorisasi. Ini kabar baik dan buruk sekaligus untuk refactor:

- **Baik:** tidak ada route middleware berbasis role yang perlu diubah (routes/web.php sama sekali bersih dari kata "role") — semua proteksi ada di dalam body controller, jadi titik perubahan sudah "terpetakan" lewat pencarian string, bukan tersembunyi di konfigurasi routing.
- **Buruk:** karena tidak tersentralisasi, ada **62 titik pemakaian string role** tersebar di 44 file berbeda, ditambah **duplikasi 2× peta label role→nama tampilan** dan **duplikasi 3× validation rule `in:admin,waspang,...`** dalam satu file yang sama. Refactor ke tabel relasional butuh menyentuh hampir semua titik ini kalau ingin tuntas, atau bisa dilakukan bertahap (lihat bagian Rekomendasi).

Total role yang ditemukan aktif dipakai di source code: **9 role** — `admin`, `superadmin`, `waspang`, `pm`, `tif`, `super_tif`, `teknisi`, `sdi`, `sdi_surveyor`.

---

## 1. Struktur Database Saat Ini

### 1.1 Tabel `users` — TIDAK dibuat lewat migration Laravel standar

Migration dasar `0001_01_01_000000_create_users_table.php` (bawaan Laravel) membuat tabel dengan kolom `id`, `name`, `email`, `password`, dst — **tapi ini BUKAN skema yang sebenarnya dipakai.** Skema live (dikonfirmasi lewat `app/Models/User.php`) punya primary key `id_user` dan kolom `nik`, `username`, `role`, `status` yang tidak ada di migration manapun. Tiga migration berikutnya secara eksplisit mengonfirmasi ini lewat komentarnya sendiri:

> *"Kolom `role` ... tidak punya migration pembuatannya sendiri di sini — kemungkinan besar dibuat lewat import SQL manual"*
> — `2026_09_01_090000_add_superadmin_and_tif_roles_to_users_table.php`

**Implikasi penting untuk refactor:** tidak ada satu migration pun yang jadi "sumber kebenaran" struktur tabel `users` saat ini. Skema aktual di server production HANYA bisa dipastikan lewat inspeksi langsung (`DESCRIBE users;` / `information_schema.columns`) di database production — bukan dengan membaca migration.

### 1.2 Kolom `role` — riwayat perubahan ENUM (3 lapis)

| Migration | Aksi |
|---|---|
| *(tidak ada / manual SQL import)* | Kolom `role` dibuat pertama kali, kemungkinan `ENUM('admin','waspang','pm','teknisi','sdi','sdi_surveyor')` — **nilai pasti tidak terkonfirmasi dari migration manapun** |
| `2026_06_03_092613_add_status_to_users_table.php` | Menambah kolom `status ENUM('active','inactive')` setelah `role` (tidak mengubah `role` itu sendiri) |
| `2026_09_01_090000_add_superadmin_and_tif_roles_to_users_table.php` | Menambah `superadmin`, `tif` ke ENUM `role` — lewat query `information_schema` + `ALTER TABLE ... MODIFY role ENUM(...)` (idempotent, hanya jalan kalau kolom terbukti ENUM) |
| `2026_09_02_090000_add_super_tif_role_to_users_table.php` | Menambah `super_tif` ke ENUM `role`, pola sama persis |

Kedua migration terakhir ini sendiri **tidak yakin** apakah kolomnya ENUM atau sudah VARCHAR — keduanya melakukan pengecekan `DATA_TYPE` dulu sebelum bertindak. Ini konfirmasi kuat bahwa dokumentasi skema selama ini tidak solid, dan jadi salah satu alasan kuat memindahkan ke tabel `roles` yang terdokumentasi jelas.

### 1.3 Foreign key & relasi terkait `users` saat ini

Dari `app/Models/User.php`, relasi yang ada:
- `assignments()` → `hasMany(ProjectAssignment::class, 'waspang_id', 'id_user')`
- `notifications()` → `hasMany(Notification::class, 'user_id')`
- `siteSurveys()` → `hasMany(SiteSurvey::class, 'surveyor_id', 'id_user')`

**Tidak ada foreign key ke tabel role apa pun** — wajar, karena role masih string. Tabel lain yang punya kolom FK ke `users.id_user` (di luar 3 relasi di atas, ditemukan lewat pemakaian role) antara lain `project_assignments` (`waspang_id`, `teknisi_id`), `pt2_assignments`, `site_surveys` (`surveyor_id`), `evidence`/`evidence_files` (`uploader` — relasi ke User).

### 1.4 Layer otorisasi: TIDAK ADA Middleware/Gate/Policy role

Diperiksa dan dikonfirmasi kosong:
- `app/Http/Middleware/` — hanya `PreventBackHistory.php` dan `VerifyWebhookApiToken.php` (untuk API webhook, tidak terkait role user). **Tidak ada middleware role-check apapun.**
- `app/Policies/` — **direktori ini tidak ada sama sekali.**
- `Gate::define(...)` — **nol hasil pencarian di seluruh `app/`.**
- `routes/web.php` — **nol match untuk kata "role"** di seluruh route grouping/middleware.

Semua proteksi role dilakukan **inline di dalam body method controller**, dengan pola yang tidak konsisten (lihat bagian 2).

---

## 2. Inventaris Pemakaian `role` di Source Code

### 2.1 Redirect/isolasi role setelah login (1 titik pusat — bagus)

`app/Http/Controllers/DashboardController.php` baris 20–52, method `index()`:
```php
$role = $user?->role;
if ($role === 'waspang')      return redirect()->route('waspang.dashboard');
if ($role === 'pm' || $role === 'tif') return redirect()->route('pm.dashboard');
if ($role === 'teknisi')      return redirect()->route('teknisi.pt2.index');
if ($role === 'sdi')          return redirect()->route('sdi.index');
if ($role === 'sdi_surveyor') return redirect()->route('surveyor.index');
if (!in_array($role, ['admin', 'superadmin', 'super_tif'], true)) abort(403);
```
Ini **satu-satunya titik pusat routing berbasis role** di seluruh aplikasi — kabar baik, karena kalau nanti role jadi relasi (`$user->role->slug` atau semacamnya), cukup 1 method ini yang perlu disesuaikan untuk alur redirect utama.

### 2.2 Guard otorisasi inline per-controller (pola `in_array($user->role, [...])`)

| File | Baris | Pola |
|---|---|---|
| `UserManagementController.php` | 17 | `if (auth()->user()?->role !== 'superadmin')` — akses tunggal superadmin |
| `GisCadController.php` | 22, 28, 42, 85, 125, 182 | `private const ALLOWED_ROLES = [...]` + 5× pengecekan berbeda-beda kombinasi role per method |
| `SurveyorController.php` | 22, 28, 42, 63, 280, 301, 355, 381 | pola identik dengan GisCadController (kemungkinan besar copy-paste) |
| `ProgramController.php` | 96–97 | `abort(403, ...)` khusus blokir `super_tif` dari 1 fitur |

**Catatan penting:** `GisCadController` dan `SurveyorController` masing-masing mendefinisikan `ALLOWED_ROLES` sendiri dengan isi **persis sama** (`['sdi_surveyor', 'admin', 'sdi', 'waspang', 'superadmin', 'super_tif']`) tapi tidak berbagi satu sumber — ini kandidat pertama yang paling gampang dirapikan lewat relasi/permission table.

### 2.3 Query filter berdasarkan role (`where`/`whereIn`)

| File:Baris | Query |
|---|---|
| `AdminPt2Controller.php:73` | `User::whereIn('role', ['teknisi','waspang'])->get()` |
| `AssignWaspangController.php:15,86` | `User::where('role','waspang')` |
| `ProgramController.php:56` | `User::whereIn('role', ['teknisi','waspang'])->get()` |
| `ProjectController.php:95` | `User::whereIn('role', ['waspang','teknisi'])->get()` |
| `DashboardPmController.php:977` | `->where('u.role','waspang')` (raw join query) |
| `WaspangController.php:1126` | `User::whereIn('role', ['admin','pm'])->get()` |

Enam titik query yang perlu disesuaikan jadi `whereHas('roleRelation', fn($q) => $q->whereIn('slug', [...]))` atau join ke tabel `roles` kalau refactor jadi relasional.

### 2.4 Pemakaian role sebagai metadata/label bisnis (bukan otorisasi)

Ini kelompok terbesar — role dipakai untuk **penamaan dinamis** dalam log aktivitas & notifikasi, BUKAN untuk cek izin:
- `ProjectController.php:138,154,161-169,183` — `$roleTitle = ucfirst($targetUser->role)`, dipakai untuk teks log (`"Assign {$roleTitle}"`) dan key dinamis (`"old_{$targetUser->role}_id"`)
- `Pt2AssignmentController.php:32,46,54-56` — pola identik untuk PT2
- `PublishStaleProjectReminders.php:90-102,160` — `$role = $assignment->waspang_id ? 'waspang' : 'teknisi'` lalu dipakai di pesan Telegram
- `TeknisiPt2Controller.php:389`, `WaspangController.php:839` — `'uploader_role' => 'teknisi'/'waspang'` hardcoded (bukan dari kolom `role` user, tapi context static)

Bagian ini **berisiko tinggi kalau berubah nama/slug role** karena teks yang dihasilkan (nama log, pesan Telegram) langsung dibaca manusia — perlu pemetaan slug→label yang konsisten kalau di-refactor.

### 2.5 Duplikasi peta label role → nama tampilan (2 lokasi, isi identik 9 role)

- `resources/views/admin/users/index.blade.php:71-79` (`$roleNames`)
- `resources/views/components/profile-account-menu.blade.php:12-21` (`$__roleLabels`)

Isinya sama persis (`admin→Approval`, `waspang→Inputer`, `teknisi→Inputer PT2`, dst) tapi ditulis independen di 2 file Blade — **kandidat paling jelas untuk pindah jadi kolom `label`/`display_name` di tabel `roles`**, menghapus 1 sumber duplikasi nyata.

### 2.6 Validasi & form role (duplikasi 3× dalam 1 file)

`UserManagementController.php` baris 53, 81, 178 — ketiganya identik:
```php
'role' => 'required|in:admin,waspang,pm,teknisi,sdi,sdi_surveyor,superadmin,tif,super_tif',
```
Daftar 9 role di-hardcode 3× dalam satu file yang sama (store, update, import CSV). Ini paling gampang dirapikan bahkan **sebelum** refactor tabel penuh — cukup jadi 1 konstanta/query `Role::pluck('slug')`.

### 2.7 Tampilan Blade — sidebar & badge role

- Sidebar berbeda per role sudah dipisah per folder (`admin/`, `pm/`, `sdi/`, `super_tif/` masing-masing punya `components/sidebar.blade.php` + `sidebar-mobile.blade.php`) — pola ini sudah rapi dari refactor sebelumnya di sesi ini, jadi role check di sini hanya untuk sub-menu di DALAM sidebar yang sama (`superadmin` vs `admin` biasa, `tif` vs `pm` biasa) — bukan pemilihan sidebar itu sendiri (itu sudah lewat `layouts/admin.blade.php`).
- `layouts/admin.blade.php:29,46` — pemilihan sidebar (`super_tif` vs default)
- `surveyor/*.blade.php`, `teknisi/profile.blade.php`, `waspang/profile.blade.php` — tampilkan `{{ auth()->user()->role }}` mentah (belum pakai label manapun, langsung nilai kolom)

### 2.8 Seeder, Factory, dan Test — TIDAK sinkron dengan skema asli

- `database/factories/UserFactory.php` — masih pola scaffolding default Laravel (`name`, `email`, `password`), **tidak ada field `role`/`nik`/`username` sama sekali** — factory ini akan GAGAL kalau dipakai untuk membuat User di skema asli (kolom `email` mungkin tidak ada di tabel real).
- `database/seeders/DatabaseSeeder.php` — memanggil `User::factory()->create(['name'=>..., 'email'=>...])`, sama-sama tidak relevan dengan skema asli.
- `tests/` — hanya scaffolding default Laravel (`Auth/*Test.php`, `ProfileTest.php`) yang mengasumsikan skema User bawaan. **Tidak ada satu test pun yang menguji logic role** di aplikasi ini.

**Implikasi:** tidak ada regression safety net otomatis untuk memverifikasi refactor role tidak merusak apa pun — pengujian manual/QA manual jadi wajib di Phase 2+.

### 2.9 API & Job — role dipakai sebagai data bebas (bukan enum ketat)

- `TelegramWebhookEvent` / `TelegramWebhookEventService` — kolom `recipient_role` di tabel `telegram_webhook_events` adalah **string bebas** (bukan FK ke `users.role`), dipakai lewat `publishToRole(string $role, ...)`. Ini **sengaja terpisah** dari konsep role user — tetap harus tetap string bebas walau `users.role` di-refactor jadi relasi, karena baris event bisa target role yang mungkin sudah tidak ada di tabel `roles` (histori tetap harus terbaca).
- `PushTelegramWebhookEventJob.php`, model `TelegramWebhookEvent.php` — hanya menyimpan ulang nilai `recipient_role`, tidak melakukan query terhadap `users.role`.

---

## 3. Ringkasan Berkas Terdampak (lengkap)

**Total 44 file** mengandung kata "role" (di luar vendor/node_modules), dengan rincian:

- **Model:** `app/Models/User.php` (kolom `role` di `$fillable`)
- **Controller (12):** `AdminPt2Controller`, `AssignWaspangController`, `DashboardController`, `DashboardPmController`, `GisCadController`, `ProgramController`, `ProjectController`, `Pt2AssignmentController`, `SurveyorController`, `TeknisiPt2Controller`, `UserManagementController`, `WaspangController`
- **Command:** `PublishStaleProjectReminders.php`
- **Job/Service (webhook, tidak terkait langsung — lihat 2.9):** `PushTelegramWebhookEventJob.php`, `TelegramWebhookEventService.php`, `TelegramWebhookEvent.php`
- **Migration (4):** riwayat perubahan ENUM (lihat 1.2)
- **Blade (20 file):** sidebar admin/pm/sdi/super_tif (×2 desktop+mobile = 8 file), `layouts/admin.blade.php`, `admin/users/index.blade.php`, `admin/inbox/history.blade.php`, `admin/projects/partials/{modals,scripts}.blade.php`, `admin/pt2/partials/modals.blade.php`, `components/{change-password-form,profile-account-menu}.blade.php`, `super_tif/dashboard.blade.php`, `surveyor/{index,show,partials/bottom-nav}.blade.php`, `teknisi/profile.blade.php`, `waspang/profile.blade.php`
- **Tidak ditemukan sama sekali:** Middleware role-check, Policy, Gate, FormRequest khusus role, API Resource yang expose role, `whereRole()` scope

---

## 4. Tingkat Risiko & Kompleksitas per Kategori

| Kategori | Jumlah titik | Risiko refactor | Catatan |
|---|---|---|---|
| Redirect setelah login | 1 method | **Rendah** | Sudah terpusat, tinggal ganti `$role` jadi hasil relasi |
| Guard otorisasi inline | ~12 titik, 4 file | **Sedang** | Ada duplikasi persis (`GisCadController` vs `SurveyorController`) — bisa dirapikan sekalian |
| Query filter user by role | 6 titik | **Rendah–Sedang** | Query sederhana, tinggal ganti ke relasi/whereHas |
| Role sebagai teks log/notifikasi | ~15 titik | **Tinggi** | Menyentuh string yang dibaca manusia (log aktivitas, pesan Telegram) — perlu hati-hati soal wording |
| Label tampilan (blade) | 2 duplikasi + beberapa raw output | **Rendah** | Paling gampang dipindah ke kolom `roles.label` |
| Validasi form (`in:...`) | 3 duplikasi (1 file) | **Rendah** | Gampang jadi 1 sumber |
| Seeder/Factory/Test | 3 file | **Tidak relevan/stale** | Sudah tidak sinkron dari awal, perlu ditulis ulang total kalau mau dipakai lagi (di luar scope role refactor) |
| DB schema asli (`users` table) | — | **Tinggi (unknown)** | Migration TIDAK merepresentasikan skema live — wajib inspeksi langsung skema production sebelum menulis migration baru |

---

## 5. Pertanyaan Terbuka yang Perlu Dikonfirmasi Sebelum Phase 2

1. **Skema live `users` yang sebenarnya** — perlu jalankan `DESCRIBE users;` atau `SHOW CREATE TABLE users;` langsung di database production/staging (saya tidak punya akses DB dari sesi ini) untuk memastikan tipe kolom `role` saat ini (ENUM 9 value, atau sudah VARCHAR seperti diduga sebagian migration).
2. **Strategi transisi kolom:** apakah `users.role` (string) akan **dihapus total** dan diganti `role_id` (FK ke `roles.id`), atau **dipertahankan sementara** berdampingan dengan `role_id` untuk masa transisi (lebih aman, memungkinkan rollback bertahap)?
3. **Struktur tabel `roles`** yang diinginkan — minimal `id`, `slug` (pengganti nilai ENUM lama, mis. `'waspang'`), `label` (pengganti 2 duplikasi `$roleNames`/`$__roleLabels`). Apakah juga perlu kolom tambahan seperti `is_active`, `sort_order`, atau bahkan tabel permission terpisah (`role_permissions`) mengingat saat ini otorisasi masih inline per-controller?
4. **`recipient_role`** di `telegram_webhook_events` — dikonfirmasi di atas (2.9) sebaiknya **tetap string bebas**, tidak ikut di-FK-kan ke `roles`, supaya histori event lama tetap valid walau daftar role berubah di masa depan. Perlu persetujuan eksplisit soal ini supaya tidak ikut ter-refactor tanpa sengaja.
5. **Prioritas eksekusi** — mengingat 62 titik pemakaian tersebar di 44 file, apakah refactor dilakukan sekaligus (big-bang) atau bertahap per-modul (misal: mulai dari validasi & label dulu yang risikonya rendah, baru masuk ke guard otorisasi dan query filter)?

---

*Dokumen ini adalah hasil audit Phase 1 — tidak ada perubahan kode atau database yang dieksekusi. Menunggu konfirmasi/arahan untuk lanjut ke Phase 2 (desain skema `roles` + migration + rencana perubahan kode).*
