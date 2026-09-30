# SOP dan Buku Saku Alur Operasional DOMPIS CONS

## Project PT3 Reguler dan PT2

**Versi:** 1.0  
**Tanggal:** 28 September 2026  
**Status dokumen:** Draf untuk validasi bisnis  
**Lingkup:** Proses end to end sejak data PID dan BOQ disiapkan sampai LOP Golive

## 1 Tujuan dan cara menggunakan buku saku

Dokumen ini menjadi panduan kerja bersama bagi Admin, Superadmin, Super TIF, Officer, Waspang, Teknisi, SDI, PM, dan TIF. Gunakan bagian alur ringkas untuk orientasi, bagian SOP per tahap saat mengeksekusi pekerjaan, dan checklist harian saat melakukan kontrol operasional.

Setiap pekerjaan harus diperlakukan per LOP. Status project induk bukan lagi sumber posisi pekerjaan. Posisi proses, persetujuan SDI, dan status Golive mengikuti data pada LOP yang bersangkutan.

## 2 Prinsip operasional utama

1. Satu LOP adalah satu unit kontrol pekerjaan, approval, timeline, dan Golive.
2. Bukti yang berstatus pending harus direview. Bukti rejected harus diperbaiki atau diunggah ulang sesuai catatan reviewer.
3. PT3 memakai urutan tahap aktif Inisiasi sampai Golive. Proses DRM tidak lagi menjadi tahap aktif.
4. PT2 memakai status Inisiasi, Survey, Instalasi, Finishing, FI OGP Golive, dan Golive. Aktivitas Dismantle tetap ada, tetapi berada di dalam tahap Finishing.
5. Hold dan Drop pada PT3 tidak menghapus histori. Resume mengembalikan LOP ke posisi sebelum Hold atau Drop.
6. Histori Survey, desain peta, BOQ Survey, approval, dan aktivitas tidak boleh dihapus untuk mengganti kondisi terbaru.
7. Submit final berarti data dikunci sesuai aturan tahap. Pastikan kelengkapan sebelum menekan Submit.

## 3 Peran dan tanggung jawab

| Peran | Tanggung jawab utama |
|---|---|
| Superadmin | Administrasi penuh, pengawasan data, assignment, approval, dan monitoring lintas area |
| Admin | Menyiapkan data PID dan BOQ, mengunggah KML awal PT3, melakukan assignment, mereview eviden, menyiapkan dokumen FI OGP, serta mengirim PT2 ke SDI |
| Super TIF | Pengawasan dan kewenangan operasional sesuai akses, termasuk approval PT3 dan PT2 |
| Officer | Monitoring, pengelolaan operasional sesuai akses, dan approval PT2. Officer tidak melakukan approval eviden PT3 |
| Waspang | Menjalankan pekerjaan PT3 mulai Survey sampai Finishing dan memperbaiki eviden yang ditolak |
| Teknisi | Menjalankan pekerjaan PT2 mulai Survey sampai Mancore dan memperbaiki eviden yang ditolak |
| SDI Surveyor | Mendukung aktivitas Site Survey, tagging tiang, rute kabel, serta GIS atau CAD sesuai penugasan |
| SDI | Melakukan verifikasi akhir Golive dan mengunggah eviden UIM untuk PT3 maupun PT2 |
| PM dan TIF | Memantau progres, aging, bottleneck, deployment, timeline, dan Kurva S sesuai akses read only |

## 4 Flow ringkas end to end

### 4.1 PT3 Reguler

**Data PID dan BOQ serta KML awal** → **Assign Waspang** → **Survey dan Finalisasi BOQ Survey** → **Perizinan** → **Material Delivery** → **Persiapan Instalasi** → **Instalasi** → **Pengukuran** → **Finishing** → **Draft dan Submit FI OGP** → **Verifikasi SDI dan Upload UIM** → **Golive**

### 4.2 PT2

**Data PID dan BOQ** → **Assign Teknisi per LOP** → **Survey dan pilih Mode A B atau C** → **Eviden Survey** → **Instalasi** → **Finishing dan Redaman** → **Dismantle bila ada** → **Mancore** → **Review dan approval Admin** → **Generate BAUT dan LACT bila diperlukan** → **Kirim ke SDI** → **Upload UIM oleh SDI** → **Golive**

## 5 SOP end to end PT3 Reguler

### 5.1 Persiapan data dan Inisiasi

**Pelaksana:** Admin atau role pengelola data yang berwenang.

**Langkah kerja:**

1. Input atau import PID dan LOP program PT3 atau Reguler.
2. Pastikan minimal identitas PID SAP dan nama LOP tersedia.
3. Input atau import BOQ Plan dan pastikan designator, kategori, pasangan `pair_code`, volume, package, serta harga package terisi sesuai kebutuhan.
4. Unggah KML atau desain awal yang akan menjadi referensi Survey.
5. Periksa Branch, STO, program, mitra, dan data lokasi.
6. Assign Waspang. Saat assignment dilakukan, LOP yang masih Inisiasi otomatis berpindah ke Survey.

**Hasil:** LOP berstatus Survey dan masuk Inbox Waspang.

**Kontrol:** Untuk PID yang memiliki lebih dari satu LOP, pastikan setiap aksi mengarah ke LOP yang benar. Jangan menggunakan alur Survey lama yang hanya mengambil LOP pertama.

### 5.2 Survey

**Pelaksana:** Waspang, dengan dukungan SDI Surveyor bila diperlukan.

**Langkah kerja:**

1. Buka LOP dari Inbox Waspang dan buka accordion Survey.
2. Periksa peta dari KML atau desain awal Admin.
3. Pilih **Sesuai** bila desain lapangan sesuai.
4. Pilih **Redesign** bila tidak sesuai. Sistem membuka Site Survey untuk tagging tiang, membuat rute kabel, dan memperbarui desain. Nama LOP mengikuti LOP aktif dan tidak diubah manual.
5. Simpan hasil Survey. Desain terbaru menjadi map aktif; versi lama tetap tersimpan sebagai histori.
6. Konfirmasi peta dengan tombol **Sesuai**.
7. Buka **Finalisasi Survey**.
8. Isi Volume Survey untuk seluruh baris yang diwajibkan. BOQ Plan bersifat terkunci.
9. Sistem menampilkan kategori designator M dan J. Pasangan dengan `pair_code` yang sama dihitung dan ditampilkan satu kali.
10. Tambahkan designator tambahan bila ditemukan di lapangan. Volume Plan pada item tambahan tetap kosong dan Volume Survey wajib diisi. Hapus item tambahan yang tidak sesuai.
11. Gunakan **Simpan Draf** bila pekerjaan belum final.
12. Tekan **Selesai Survey** setelah seluruh volume benar.

**Gate dan hasil:**

- Peta harus sudah dikonfirmasi Sesuai.
- BOQ Plan harus tersedia.
- Package dan harga designator harus lengkap agar deviasi nominal dapat dihitung.
- Bila deviasi nominal BOQ Survey terhadap BOQ Plan tidak lebih dari 10 persen, LOP langsung berpindah ke Perizinan.
- Bila deviasi lebih dari 10 persen, LOP tetap di Survey dan meminta bukti persetujuan Redesign. Setelah bukti diunggah, LOP berpindah ke Perizinan.
- Setiap penyelesaian atau Re Survey membuat ronde dan snapshot histori baru.

### 5.3 Perizinan

**Pelaksana:** Waspang.

**Langkah kerja:**

1. Pilih kategori perizinan pada form **Add Perizinan**.
2. Isi tanggal kegiatan dan catatan kronologi.
3. Lampirkan foto atau PDF bila tersedia. Lampiran dapat berisi eviden perizinan atau BA KP.
4. Tambahkan kronologi baru setiap ada perkembangan. Entri lama tidak ditimpa.
5. Setelah minimal satu aktivitas Perizinan tersimpan, tekan **Perizinan Selesai**.

**Hasil:** LOP berpindah dari Perizinan ke Material Delivery.

**Kontrol:** Tombol Update Kronologi universal tetap dapat digunakan. Tidak ada proses DRM di antara Survey dan Perizinan.

### 5.4 Material Delivery

**Pelaksana:** Waspang.

**Langkah kerja:**

1. Unggah minimal satu foto material delivery.
2. Tambahkan deskripsi atau kronologi bila diperlukan.
3. Tekan **Selesai Material Delivery**.

**Hasil:** LOP berpindah ke Persiapan Instalasi.

### 5.5 Persiapan Instalasi

**Pelaksana:** Waspang. **Reviewer:** Admin, Superadmin, atau Super TIF.

**Langkah kerja:**

1. Unggah Eviden Barang Tiba.
2. Unggah Eviden Perizinan.
3. Bila ada eviden rejected, baca catatan reviewer dan unggah ulang.
4. Setelah kedua kategori tersedia dan tidak ada yang rejected, tekan **Lanjut ke Instalasi**.

**Hasil:** LOP berpindah ke Instalasi. Eviden pending tetap masuk antrean approval Admin.

### 5.6 Instalasi

**Pelaksana:** Waspang. **Reviewer:** Admin, Superadmin, atau Super TIF.

**Acuan item:** BOQ Survey ronde terbaru yang selesai. Jika belum ada ronde Survey selesai, sistem memakai BOQ Plan.

**Langkah kerja:**

1. Buka setiap item material pada checklist Instalasi.
2. Input volume aktual pekerjaan.
3. Unggah eviden Progress BOQ untuk setiap item yang berlaku.
4. Kirim dan pantau status eviden.
5. Perbaiki setiap item rejected berdasarkan catatan reviewer.

**Gate dan hasil:** Seluruh item material harus memiliki eviden dan seluruh eviden terkait harus approved. Setelah gate terpenuhi, LOP berpindah ke Pengukuran.

### 5.7 Pengukuran

**Pelaksana:** Waspang. **Reviewer:** Admin, Superadmin, atau Super TIF.

Lima kategori Pengukuran adalah:

1. OTDR.
2. File SOR.
3. OPM.
4. Kedalaman Galian.
5. Eviden Pengukuran Lainnya.

**Langkah kerja:**

1. Unggah eviden untuk setiap kategori yang tersedia.
2. Jika suatu kategori memang tidak berlaku, pilih **Tidak Ada** atau N A dan isi catatan bila diminta.
3. Pantau approval dan perbaiki eviden rejected.

**Gate dan hasil:** Kelima kategori harus berstatus approved atau ditandai Tidak Ada. Setelah lengkap, LOP berpindah ke Finishing. File SOR dan Eviden Pengukuran Lainnya memakai nama kanonik tersebut; data lama tetap dikenali oleh sistem.

### 5.8 Finishing

**Pelaksana:** Waspang. **Reviewer:** Admin, Superadmin, atau Super TIF.

**Langkah kerja:**

1. Sistem menampilkan item dari BOQ Survey ronde terbaru, dengan fallback ke BOQ Plan.
2. Unggah eviden Final hanya untuk designator yang ditandai membutuhkan eviden Finishing.
3. Pantau approval dan perbaiki eviden rejected.

**Gate dan hasil:** Semua eviden Final yang wajib harus approved. Bila tidak ada designator yang membutuhkan eviden Final, tahap dianggap selesai dengan label **Tidak Ada Item Wajib**. LOP tetap berada di Finishing sampai Admin melakukan Submit FI OGP.

### 5.9 FI OGP Golive

**Pelaksana:** Admin, Superadmin, atau Super TIF.

Empat kategori wajib adalah:

1. Capture Valins.
2. PDF ABD dan Valid4.
3. File KML.
4. Mancore dalam bentuk foto atau Excel.

**Langkah kerja:**

1. Unggah dokumen secara bertahap dan tekan **Save Draft**.
2. Selama masih draft, Admin dapat menambah atau menghapus file.
3. Pastikan empat kategori sudah lengkap.
4. Pastikan gate Instalasi, Pengukuran, dan Finishing sudah selesai.
5. Tekan **Submit**.

**Gate dan hasil:** Tombol Submit hanya dapat digunakan setelah empat kategori lengkap. Submit ditolak bila status LOP belum Finishing atau approval tahap sebelumnya belum lengkap. Setelah Submit, dokumen dikunci, tidak dapat dihapus atau diunggah ulang, dan LOP berpindah ke FI OGP Golive untuk menunggu SDI.

### 5.10 Verifikasi SDI dan Golive

**Pelaksana:** SDI.

**Langkah kerja:**

1. Buka antrean Golive PT3.
2. Periksa empat kategori dokumen FI OGP yang sudah disubmit Admin.
3. Unggah Capture UIM.
4. Lakukan verifikasi.

**Hasil akhir:** Status LOP menjadi Golive, `is_golive` aktif, dan waktu Golive tercatat. Eviden UIM dikunci setelah verifikasi.

## 6 SOP end to end PT2

### 6.1 Persiapan data dan assignment

**Pelaksana:** Admin atau role pengelola yang berwenang.

**Langkah kerja:**

1. Input atau import PID PT2.
2. Pastikan setiap LOP mempunyai ID IHLD dan nama LOP. Satu PID PT2 dapat memiliki banyak LOP.
3. Lengkapi BOQ dan data Branch, STO, mitra, serta lokasi.
4. Assign Teknisi untuk setiap LOP.

**Hasil:** LOP berpindah dari Inisiasi ke Survey dan masuk Inbox Teknisi yang ditugaskan.

### 6.2 Step 1 Survey dan eviden persiapan

**Pelaksana:** Teknisi. **Reviewer:** role approval PT2 yang berwenang.

**Langkah kerja:**

1. Pilih kondisi Survey: **Eksekusi** atau **Kendala**.
2. Bila Kendala, isi catatan. LOP tetap di Survey dan menunggu review kendala.
3. Bila Eksekusi, pilih Mode A, B, atau C dan isi data teknis serta BOQ material.
4. Unggah eviden Survey sesuai mode:
   - Mode A: Power IN dan Power OUT.
   - Mode B: Base Tray Feeder, Base Tray Distribusi, Power IN Feeder, dan Power OUT Splitter Existing.
   - Mode C: Base Tray Feeder dan Base Tray Distribusi.
5. Pantau approval dan perbaiki eviden yang rejected.

**Hasil:** Data Survey dan eviden tersimpan. Status operasional tetap Survey sampai aktivitas Instalasi dimulai.

### 6.3 Step 2 Instalasi

**Pelaksana:** Teknisi. **Reviewer:** Admin, Superadmin, Super TIF, atau Officer.

**Langkah kerja:**

1. Unggah Foto Material atau Barang Tiba.
2. Unggah Foto Progress Instalasi.
3. Pantau approval dan perbaiki eviden rejected.

**Hasil:** Setelah upload, status LOP minimal menjadi Instalasi.

### 6.4 Step 3 Finishing dan Redaman

**Pelaksana:** Teknisi. **Reviewer:** Admin, Superadmin, Super TIF, atau Officer.

**Langkah kerja:**

1. Unggah eviden Redaman Port sesuai mode.
2. Mode A memakai target 16 port.
3. Mode B memakai target 8 port terbaru.
4. Mode C memilih 8 atau 16 port sesuai kondisi.
5. Unggah foto tambahan bila diperlukan.
6. Pantau approval dan perbaiki eviden rejected.

**Hasil:** Status LOP menjadi Finishing.

### 6.5 Step 4 Dismantle

**Pelaksana:** Teknisi. **Reviewer:** Admin, Superadmin, Super TIF, atau Officer.

**Langkah kerja:**

1. Isi material ODP yang dibongkar bila ada.
2. Isi jenis dan jumlah Splitter yang dibongkar bila ada.
3. Unggah eviden foto jika tersedia.
4. Jika tidak ada material Dismantle, pilih kondisi tidak ada sesuai form.

**Hasil:** Aktivitas Dismantle tersimpan sebagai bagian dari Finishing. Tidak ada status `dismantle` terpisah pada flow terbaru.

### 6.6 Step 5 Mancore

**Pelaksana:** Teknisi.

**Langkah kerja:**

1. Isi Label ODP.
2. Isi Label ODC.
3. Isi Distribusi Core.
4. Isi Feeder Core.
5. Simpan dan Submit.

**Hasil:** Status LOP menjadi FI OGP Golive dan menunggu review Admin.

### 6.7 Review Admin dan dokumen pendukung

**Pelaksana:** Admin, Superadmin, Super TIF, atau Officer sesuai akses PT2.

**Langkah kerja:**

1. Review Survey dan seluruh eviden per langkah.
2. Approve eviden yang valid.
3. Reject eviden yang tidak valid dan wajibkan catatan koreksi.
4. Pastikan Teknisi memperbaiki eviden rejected sampai approved.
5. Review data Dismantle dan Mancore.

**Jalur dokumen pendukung:**

- BAUT dapat dibuat setelah seluruh eviden pada LOP approved.
- LACT dapat dibuat setelah seluruh eviden approved dan BAUT sudah final.
- BAUT dan LACT adalah dokumen pendukung. Pembuatan dokumen tidak mengubah `status_progress` LOP.

### 6.8 Kirim ke SDI

**Pelaksana:** Admin atau role approval PT2 yang berwenang.

**Langkah kerja:**

1. Pastikan eviden dan data Mancore telah direview.
2. Tekan **Kirim ke SDI**.

**Hasil:** `sdi_approval_status` menjadi pending. LOP masuk antrean verifikasi SDI dan tetap berada pada FI OGP Golive.

### 6.9 Verifikasi SDI dan Golive

**Pelaksana:** SDI.

**Langkah kerja:**

1. Buka antrean Golive PT2.
2. Review LOP yang dikirim Admin.
3. Unggah Eviden UIM.
4. Submit verifikasi Golive.

**Hasil akhir:** `sdi_approval_status` menjadi approved, `is_golive` aktif, bukti dan tanggal Golive tersimpan. Tampilan LOP menjadi Golive.

## 7 Aturan approval dan koreksi

| Kondisi | Tindakan pelaksana | Tindakan reviewer | Dampak |
|---|---|---|---|
| Pending | Tunggu review dan pantau Inbox | Approve atau Reject | Belum final |
| Approved | Tidak perlu unggah ulang | Tidak ada tindakan kecuali reset yang sah | Dihitung memenuhi gate |
| Rejected | Baca catatan lalu unggah ulang | Review ulang file pengganti | Gate belum terpenuhi |
| Draft FI OGP PT3 | Boleh tambah atau hapus file | Belum masuk antrean SDI | Status LOP belum berubah |
| Submitted FI OGP PT3 | Tidak dapat mengubah dokumen | SDI melakukan verifikasi | Dokumen terkunci |
| Tidak Ada pada Pengukuran PT3 | Isi pilihan N A dan catatan bila perlu | Tidak membutuhkan file | Dihitung selesai untuk item tersebut |

## 8 Alur pengecualian

### 8.1 Re Survey PT3

Re Survey dapat dimulai setelah Survey pertama selesai. Sistem membuat ronde baru dan mengembalikan posisi LOP ke Survey. Ronde lama, snapshot BOQ, dan desain lama tetap menjadi histori. Selesaikan ronde yang sedang berjalan sebelum membuka ronde lain.

### 8.2 Hold dan Drop PT3

Hold digunakan untuk jeda sementara. Drop digunakan untuk menghentikan LOP dari alur normal. Keduanya menyimpan tahap sebelumnya. Bila keputusan bisnis mengizinkan resume, sistem mengembalikan LOP ke tahap sebelum Hold atau Drop.

### 8.3 Kendala PT2

Teknisi memilih Kendala pada Survey dan mengisi alasan. LOP menunggu review. Bila ditolak atau perlu koreksi, Teknisi memperbarui data Survey. Jangan melanjutkan ke eviden Survey saat kondisi kendala belum diselesaikan.

### 8.4 Eviden rejected

Pelaksana harus membuka catatan reviewer, mengganti eviden yang salah, dan memastikan status kembali pending. Reviewer kemudian melakukan review ulang. Jangan menghapus histori approval untuk menyembunyikan penolakan.

### 8.5 Data tidak lengkap

- PT3 Survey tidak dapat difinalisasi jika BOQ Plan, package, atau harga designator yang dibutuhkan belum lengkap.
- PT3 FI OGP tidak dapat disubmit bila empat kategori dokumen belum lengkap atau gate tahap sebelumnya belum selesai.
- PT2 BAUT tidak dapat difinalkan bila masih ada eviden yang belum approved.
- PT2 LACT tidak dapat difinalkan sebelum BAUT final dan seluruh eviden approved.

## 9 Monitoring dan pelaporan

### 9.1 Main Monitoring

Tersedia untuk Superadmin, Admin, TIF, Officer, Super TIF, dan PM.

- **Monitoring Approval** menampilkan transaksi eviden pending. Admin dapat memilih Inbox Saya untuk assignment miliknya atau Semua Antrean.
- **Monitoring Operational** menampilkan LOP aktif, bottleneck, belum diassign ke pelaksana, pending approval, summary per Admin, summary per Branch, umur tahap, dan lama tidak bergerak.
- Ambang bottleneck dapat dipilih 1, 3, 7, 14, atau 30 hari kalender.
- Angka summary dapat diklik untuk drill down ke LOP penyebab.

### 9.2 Report Deployment

Report Deployment memuat PT3 dan PT2 dengan breakdown Region, Branch, dan status. Tab Summary Deployment menampilkan pergerakan harian. Detail per Staging memakai matriks per Branch dengan kolom Bergerak dan Tidak Bergerak pada setiap step atau substep. Angka dapat diklik untuk melihat LOP, aktivitas, dan pelaku.

### 9.3 Timeline

Timeline PT3 dan PT2 bersifat read only dan dapat diakses oleh Admin, Superadmin, Super TIF, Officer, PM, dan TIF. Timeline PT2 ditampilkan per LOP karena satu PID dapat memiliki banyak LOP.

### 9.4 Kurva S

Kurva S tahap pertama hanya tersedia untuk PT3 atau Reguler melalui aksi pada Project ID per LOP. Target dihitung dari `start_tgl`, durasi Survey, Perizinan, Material Delivery, Instalasi berdasarkan BOQ, dan tiga hari Golive setelah syarat FI OGP lengkap. Realisasi mengikuti aktivitas dan eviden aktual. Bila `start_tgl` atau kategori Perizinan belum tersedia, Kurva S belum dapat dihitung.

## 10 Checklist harian per peran

### 10.1 Admin

- Periksa data PID, LOP, BOQ, KML, Branch, STO, package, dan assignment.
- Buka Main Monitoring dan periksa Inbox Saya.
- Dahulukan approval dengan aging tertua.
- Berikan catatan yang jelas saat Reject.
- Periksa LOP tanpa pergerakan dan tanpa pelaksana.
- Siapkan draft FI OGP PT3, lalu Submit hanya setelah seluruh gate selesai.
- Untuk PT2, review seluruh langkah dan kirim ke SDI setelah data siap.

### 10.2 Waspang PT3

- Buka Inbox dan kerjakan LOP sesuai urutan status.
- Simpan draf sebelum meninggalkan Finalisasi Survey.
- Tambahkan kronologi setiap ada perkembangan lapangan.
- Periksa eviden rejected dan unggah ulang pada hari yang sama bila memungkinkan.
- Jangan melewati item Pengukuran. Pilih Tidak Ada hanya bila benar-benar tidak berlaku.

### 10.3 Teknisi PT2

- Pastikan LOP yang dibuka sesuai assignment.
- Pilih mode Survey sesuai kondisi lapangan.
- Unggah eviden pada langkah yang tepat.
- Lengkapi Redaman, Dismantle, dan Mancore sebelum Submit.
- Pantau rejection dan segera lakukan koreksi.

### 10.4 SDI

- Periksa antrean Waiting Approval PT3 dan PT2.
- Cocokkan dokumen final dengan LOP yang benar.
- Unggah UIM hanya setelah review selesai.
- Pastikan status akhir Golive dan bukti tersimpan.

### 10.5 PM TIF Officer dan Super TIF

- Pantau Main Monitoring, Report Deployment, Summary Deployment, dan Timeline.
- Fokus pada bottleneck, LOP tidak bergerak, antrean approval, dan assignment kosong.
- Gunakan drill down untuk menentukan PIC dan tindakan koreksi.
- Gunakan Kurva S PT3 untuk melihat deviasi target dan realisasi per LOP.

## 11 Referensi status cepat

### 11.1 Status aktif PT3

| Urutan | Status | Pemicu masuk | Pemicu keluar |
|---:|---|---|---|
| 1 | Inisiasi | PID dan LOP dibuat | Assign Waspang |
| 2 | Survey | Waspang diassign | Finalisasi Survey dan gate deviasi selesai |
| 3 | Perizinan | Survey selesai | Minimal satu Add Perizinan lalu Perizinan Selesai |
| 4 | Material Delivery | Perizinan selesai | Minimal satu eviden lalu Selesai Material Delivery |
| 5 | Persiapan Instalasi | Material Delivery selesai | Barang Tiba dan Perizinan tersedia serta tidak rejected |
| 6 | Instalasi | Persiapan Instalasi selesai | Seluruh eviden item material approved |
| 7 | Pengukuran | Instalasi approved | Lima item approved atau N A |
| 8 | Finishing | Pengukuran selesai | Eviden Final wajib approved atau tidak ada item wajib |
| 9 | FI OGP Golive | Empat dokumen disubmit Admin | Capture UIM diverifikasi SDI |
| 10 | Golive | Verifikasi SDI | Status akhir |

Status khusus PT3: Hold dan Drop. DRM hanya dipertahankan sebagai histori teknis dan tidak tampil dalam flow aktif.

### 11.2 Status aktif PT2

| Urutan | Status | Pemicu |
|---:|---|---|
| 1 | Inisiasi | LOP dibuat atau diimport |
| 2 | Survey | Teknisi diassign atau Survey disimpan |
| 3 | Instalasi | Eviden Instalasi diunggah |
| 4 | Finishing | Eviden Redaman atau Dismantle disimpan |
| 5 | FI OGP Golive | Mancore disimpan |
| 6 | Golive | SDI mengunggah UIM dan menyetujui |

Status Drop tersedia untuk PT2. Istilah lama Preparation, Progress, Finish, Redaman, Dismantle, Mancore, Done, dan Complete tidak digunakan sebagai status operasional terbaru.

## 12 Matriks gate akhir

| Flow | Gate yang harus lengkap | Pihak berikutnya |
|---|---|---|
| PT3 Survey ke Perizinan | Peta Sesuai, seluruh Volume Survey, data harga lengkap, persetujuan redesign bila deviasi lebih dari 10 persen | Waspang pada Perizinan |
| PT3 Instalasi ke Pengukuran | Eviden setiap item material approved | Waspang dan Admin |
| PT3 Pengukuran ke Finishing | Lima item approved atau N A | Waspang dan Admin |
| PT3 Finishing ke FI OGP | Eviden Final wajib approved serta empat kategori FI OGP lengkap dan disubmit | SDI |
| PT3 FI OGP ke Golive | Capture UIM diunggah dan diverifikasi | Selesai |
| PT2 kerja lapangan ke review | Survey, Instalasi, Finishing, Dismantle bila ada, dan Mancore tersimpan | Admin atau reviewer PT2 |
| PT2 ke SDI | Review selesai dan Admin menekan Kirim ke SDI | SDI |
| PT2 ke Golive | Eviden UIM diunggah dan diverifikasi | Selesai |

## 13 Eskalasi dan kontrol perubahan

Eskalasi ke Admin atau Superadmin jika LOP tidak dapat berpindah tahap walaupun gate sudah terpenuhi, assignment mengarah ke pelaksana yang salah, BOQ atau package belum lengkap, file tidak muncul pada halaman approval, atau status Golive tidak berubah setelah verifikasi.

Saat membuat tiket koreksi, lampirkan PID, nama LOP, Branch, STO, tahap aktif, nama pengguna, waktu kejadian, tangkapan layar, dan pesan error. Jangan mengubah status langsung di database sebagai jalan pintas. Koreksi harus mempertahankan histori aktivitas dan approval.

Dokumen ini harus direview kembali setiap ada perubahan status, eviden wajib, role approval, atau gate proses. Versi berikutnya perlu disahkan oleh pemilik proses operasional sebelum dipakai sebagai SOP formal.
