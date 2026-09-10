<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DesignatorController;
use App\Http\Controllers\WaspangController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\DesignatorPriceController;
use App\Http\Controllers\AssignWaspangController;
use App\Http\Controllers\DashboardPmController;
use App\Http\Controllers\TeknisiPt2Controller;
use App\Http\Controllers\AdminPt2Controller;
use App\Http\Controllers\Pt2AssignmentController;
use App\Http\Controllers\SdiController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SurveyorController;
use App\Http\Controllers\GisCadController;
use App\Http\Controllers\ProjectStageController;
use App\Http\Controllers\KendalaCategoryController;
use App\Http\Controllers\PermitCategoryController;

/*
|--------------------------------------------------------------------------
| ROLE GROUPS (2026-09-08) -- referensi untuk seluruh ->middleware('role:..')
| di bawah. Ditentukan dari (a) guard manual yang SUDAH ADA di controller
| (DashboardController::index(), GisCadController/SurveyorController::
| guardAccess(), UserManagementController::ensureSuperAdminOrOfficer()), dan
| (b) route mana yang benar-benar dipakai tiap sidebar role (grep
| resources/views/{role}/components/sidebar*.blade.php). Rincian per-grup
| ada di komentar masing-masing section. Lihat ANALISA_REFACTOR_PERSIAPAN.md
| bagian K.6 untuk audit lengkapnya -- MOHON DIREVIEW, karena beberapa
| batasan (terutama exclude officer dari Approval Eviden/Master Designator/
| Bulk Import) diturunkan dari sidebar & komentar migration seed_officer_role,
| bukan dari test langsung (saya tidak bisa live-test perubahan ini).
|
| PENTING: route 'dashboard' SENGAJA TIDAK diberi role gate -- ini dipakai
| SEMUA controller Auth (login/register/verify-email/dst) sebagai target
| redirect universal, lalu DashboardController::index() sendiri yang
| meneruskan tiap role ke dashboard rolenya masing-masing. Kalau route ini
| digembok role tertentu, role lain akan 403 tepat setelah login.
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| WELCOME
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD (universal, TANPA role gate -- lihat catatan di atas)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Profile akun sendiri -- tidak berisiko privilege escalation, dibiarkan
    // terbuka untuk semua role yang sudah login.
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| ADMIN GENERAL (dashboard, project, BOQ, assign waspang/teknisi)
|--------------------------------------------------------------------------
| Role: admin, superadmin, super_tif, officer -- persis sama dengan guard
| yang SUDAH ADA di DashboardController::index() (baris 52 file itu),
| dipakai untuk gerbang dashboard admin & seluruh menu turunannya. Officer
| ikut di sini karena "menu sama seperti Admin" per migration seed_officer_role
| KECUALI 3 hal yang di-exclude secara eksplisit di section terpisah di
| bawah (Approval Eviden, Master Designator, Bulk Import Data).
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,superadmin,super_tif,officer'])->group(function () {

    Route::get('/admin/dashboard/matrix-detail', [DashboardController::class, 'matrixDetail'])
        ->name('admin.dashboard.matrix-detail');

    Route::get('/admin/map-monitoring', [DashboardController::class, 'mapMonitoring'])
        ->name('admin.map.monitoring');

    Route::get('/admin/inbox', [DashboardController::class, 'adminInbox'])
         ->name('admin.inbox');
    Route::get('/admin/inbox/pt2', [\App\Http\Controllers\DashboardController::class, 'adminInboxPt2'])->name('admin.inbox.pt2');

    Route::get('/admin/history', [DashboardController::class, 'adminHistory'])
        ->name('admin.history');

    Route::get('/admin/projects/{id}', [DashboardController::class, 'show'])
        ->name('admin.projects.show');

    /*
    |--------------------------------------------------------------------------
    | REKAP PROGRESS MENU ROLE ADMIN
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/rekap-progress', [DashboardController::class, 'rekapProgress'])->name('admin.rekap_progress');

    /*
    |--------------------------------------------------------------------------
    | PROJECT / LOP & BOQ
    |--------------------------------------------------------------------------
    */

    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index');

    Route::post('/projects/{project}/upload-kml', [ProjectController::class, 'uploadKml'])
        ->name('projects.upload-kml');

    Route::get('/projects/{project}/view-kml', [ProjectController::class, 'viewKml'])
        ->name('projects.view-kml');

    /*
    |--------------------------------------------------------------------------
    | DETAIL PROJECT
    |--------------------------------------------------------------------------
    */

    Route::get('/projects/{id}', [ProjectController::class, 'show'])
        ->name('projects.show');

    /*
    |--------------------------------------------------------------------------
    | CRUD PROJECT
    |--------------------------------------------------------------------------
    */

    Route::post('/projects/store', [ProjectController::class, 'store'])
        ->name('projects.store');

    Route::put('/projects/update/{id}', [ProjectController::class, 'update'])
        ->name('projects.update');

    Route::delete('/projects/delete/{id}', [ProjectController::class, 'destroy'])
        ->name('projects.destroy');

    /*
    |--------------------------------------------------------------------------
    | ASSIGN WASPANG & REMOVE
    |--------------------------------------------------------------------------
    */

    Route::post('/projects/assign', [ProjectController::class, 'assignWaspang'])
        ->name('projects.assign');

    Route::delete('/projects/assign/remove/{project}', [ProjectController::class, 'removeAssign'])
        ->name('projects.assign.remove');

    //MENU ASSIGN WASPANG
    Route::get('/assign-waspang', [AssignWaspangController::class, 'index'])
        ->name('assign-waspang.index');

    Route::get('/assign-waspang/{id}/history', [AssignWaspangController::class, 'history'])
        ->name('admin.assign-waspang.history');

    /*
    |--------------------------------------------------------------------------
    | EXPORT & IMPORT
    |--------------------------------------------------------------------------
    */

    //Route::get('/projects/export/csv', [ProjectController::class, 'exportCsv'])
        //->name('projects.export.csv');
    Route::post('/projects/import/csv', [ProjectController::class, 'importCsv'])
    ->name('projects.import.csv');

    /*
    |--------------------------------------------------------------------------
    | BOQ
    |--------------------------------------------------------------------------
    */

    Route::post('/boq/store', [ProjectController::class, 'storeBoq'])
        ->name('boq.store');

    // Route untuk menghapus item designator secara satuan
    Route::delete('/projects/boq/{id}', [ProjectController::class, 'destroyBoq'])
        ->name('projects.boq.destroy');

    Route::post('/projects/boq/store', [ProjectController::class, 'storeBoq'])
        ->name('projects.boq.store');

    Route::get('/admin/projects/{id}/review-boq', [ProjectController::class, 'reviewBoq'])
        ->name('admin.projects.review_boq');

    Route::get('/admin/projects/{project}/review-finishing', [ProjectController::class, 'reviewFinishing'])
        ->name('admin.projects.review.finishing');

    // Route untuk halaman preview daftar berkas yang akan di-download
    Route::get('/admin/projects/{id}/download-preview', [ProjectController::class, 'downloadPreview'])
        ->name('admin.projects.download_preview');

    // Route API eksekusi generate & stream ZIP file
    Route::get('/admin/projects/{id}/download-zip', [ProjectController::class, 'downloadZip'])
        ->name('admin.projects.download_zip');

    Route::get('/admin/projects/{project}/tracking', [DashboardController::class, 'tracking'])
        ->name('admin.projects.tracking');
});

/*
|--------------------------------------------------------------------------
| APPROVAL EVIDEN (role: admin, superadmin, super_tif -- OFFICER DI-EXCLUDE)
|--------------------------------------------------------------------------
| Sesuai migration seed_officer_role.php: "Menu sama seperti Admin KECUALI
| ... Approval Eviden". Dikonfirmasi juga lewat grep sidebar officer -- nol
| link ke admin.evidences.*.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,superadmin,super_tif'])->group(function () {

    Route::get('/admin/evidences/approval', [ProjectController::class, 'approvalIndex'])
    ->name('admin.evidences.approval');

    Route::post('/admin/evidences/{id}/approve', [ProjectController::class, 'approveEvidence'])
    ->name('admin.evidences.approve');

    Route::post('/admin/evidences/{id}/reject', [ProjectController::class, 'rejectEvidence'])
    ->name('admin.evidences.reject');

    Route::post('/admin/evidences/bulk-review', [ProjectController::class, 'bulkReviewEvidence'])
    ->name('admin.evidences.bulkReview');

    Route::get('/admin/evidences/review', [ProjectController::class, 'reviewIndex'])
    ->name('admin.evidences.review');

    Route::get('/admin/evidences/review/{project}', [ProjectController::class, 'reviewProject'])
        ->name('admin.evidences.review.project');

    Route::post('/admin/evidences/{id}/reset', [ProjectController::class, 'resetEvidence'])
    ->name('admin.evidences.reset');

    Route::get('/admin/evidences/review/{project}/instalasi', [ProjectController::class, 'reviewInstalasi'])
    ->name('admin.evidences.review.instalasi');

    Route::get('/admin/evidences/review/{project}/pengukuran', [ProjectController::class, 'reviewPengukuran'])
    ->name('admin.evidences.review.pengukuran');

    Route::get('/admin/evidences/review/{project}/finishing', [ProjectController::class, 'reviewFinishing'])
    ->name('admin.evidences.review.finishing');

    Route::post('/admin/evidences/bulk-approve', [\App\Http\Controllers\ProjectController::class, 'bulkApprove'])
    ->name('admin.evidences.bulk-approve');
});

/*
|--------------------------------------------------------------------------
| WASPANG MOBILE (role: waspang -- konfirmasi lewat DashboardController::
| index() yang HANYA redirect role 'waspang' ke sini, & nol sidebar role
| lain yang link ke route waspang.*)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:waspang'])->group(function () {

    Route::get('/waspang/dashboard', [WaspangController::class, 'dashboard'])
        ->name('waspang.dashboard');

    Route::get('/waspang/inbox', [WaspangController::class, 'inbox'])
        ->name('waspang.inbox');

    Route::get('/waspang/projects/{id}', [WaspangController::class, 'show'])
        ->name('waspang.projects.show');

    Route::get('/waspang/profile', [WaspangController::class, 'profile'])
        ->name('waspang.profile');

    Route::get('/waspang/notifications', [WaspangController::class, 'notifications'])
        ->name('waspang.notifications');

    /*
    |--------------------------------------------------------------------------
    | WASPANG STAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    Route::get('/waspang/projects/{id}/persiapan', [WaspangController::class, 'persiapan'])
    ->name('waspang.projects.persiapan');

    // Revisi stepper: Step 2 baru "Persiapan Instalasi" (2 kartu Barang
    // Tiba/Perizinan, persis pola halaman Persiapan sebelum refactor 5
    // sub-step) -- sisipan antara Step 1 Persiapan & Step 3 Instalasi.
    Route::get('/waspang/projects/{id}/persiapan-instalasi', [WaspangController::class, 'persiapanInstalasi'])
        ->name('waspang.projects.persiapan-instalasi');

    Route::get('/waspang/projects/{id}/instalasi', [WaspangController::class, 'instalasi'])
        ->name('waspang.projects.instalasi');

    Route::get('/waspang/projects/{id}/pengukuran', [WaspangController::class, 'pengukuran'])
        ->name('waspang.projects.pengukuran');

    Route::get('/waspang/projects/{id}/finishing', [WaspangController::class, 'finishing'])
        ->name('waspang.projects.finishing');

    Route::get('/waspang/projects/{id}/review-final', [WaspangController::class, 'reviewFinal'])
        ->name('waspang.projects.review_final');

    // Route untuk Replace Eviden (Update)
    Route::post('/waspang/evidence/{id}/replace', [\App\Http\Controllers\WaspangController::class, 'replace'])
        ->name('waspang.evidence.replace');

    /*
    |--------------------------------------------------------------------------
    | UPLOAD EVIDENCE
    |--------------------------------------------------------------------------
    */

    Route::post('/waspang/projects/{id}/evidence/upload', [WaspangController::class, 'uploadEvidence'])
    ->name('waspang.evidence.upload');

    Route::delete('/waspang/evidence/{id}/delete', [WaspangController::class, 'deleteEvidence'])
    ->name('waspang.evidence.delete');

    Route::get('/waspang/ready-ut', [WaspangController::class, 'readyUt'])
    ->name('waspang.ready-ut');

    Route::delete('/waspang/notifications/clear', [WaspangController::class, 'clearNotifications'])
    ->name('waspang.notifications.clear');

    Route::delete('/waspang/notifications/{id}', [WaspangController::class, 'deleteNotification'])
        ->name('waspang.notifications.delete');

    Route::post('/waspang/projects/{project}/issues', [WaspangController::class, 'storeIssue'])
            ->name('waspang.projects.issues.store');

    Route::post('/waspang/projects/{project}/issues/resume', [WaspangController::class, 'resumeIssue'])
            ->name('waspang.projects.issues.resume');

    // Stage 4: toggle "Tidak Ada" (N/A) per item pengukuran (gate nyata
    // lop_measurement_checks -- lihat Project::progressSummary()).
    Route::post('/waspang/projects/{project}/measurement-check/{itemKey}', [WaspangController::class, 'toggleMeasurementCheck'])
            ->name('waspang.measurement-check.toggle');

    /*
    |--------------------------------------------------------------------------
    | STAGE 4d -- SUB-STEP PERSIAPAN BARU (Survey/Perizinan/Material
    | Delivery) & tombol universal Kronologi. Lihat
    | ANALISA_REFACTOR_PERSIAPAN.md bag. Q.2.
    |--------------------------------------------------------------------------
    */

    // Survey: konfirmasi/desain peta, draf volume, dan finalisasi BOQ.
    Route::post('/waspang/projects/{project}/survey/redesign', [WaspangController::class, 'startSurveyRedesign'])
        ->name('waspang.survey.redesign');

    Route::post('/waspang/projects/{project}/survey/map/confirm', [WaspangController::class, 'confirmSurveyMap'])
        ->name('waspang.survey.map.confirm');

    Route::post('/waspang/projects/{project}/survey/boq/draft', [WaspangController::class, 'saveSurveyBoqDraft'])
        ->name('waspang.survey.boq.draft');

    Route::post('/waspang/projects/{project}/survey/boq/additional', [WaspangController::class, 'addSurveyBoqItem'])
        ->name('waspang.survey.boq.additional.store');

    Route::delete('/waspang/projects/{project}/survey/boq/additional/{boq}', [WaspangController::class, 'deleteSurveyBoqItem'])
        ->name('waspang.survey.boq.additional.delete');

    Route::post('/waspang/projects/{project}/survey/finish', [WaspangController::class, 'finishSurvey'])
        ->name('waspang.survey.finish');

    // Perizinan
    Route::post('/waspang/projects/{project}/perizinan/category', [WaspangController::class, 'updatePerizinanCategory'])
        ->name('waspang.perizinan.category');

    Route::post('/waspang/projects/{project}/perizinan/selesai', [WaspangController::class, 'togglePerizinanSelesai'])
        ->name('waspang.perizinan.selesai');

    // Material Delivery
    Route::post('/waspang/projects/{project}/material-delivery/finish', [WaspangController::class, 'finishMaterialDelivery'])
        ->name('waspang.material-delivery.finish');

    // Tombol final "Lanjut ke Persiapan Instalasi / Step 2 Instalasi" --
    // muncul setelah semua 5 sub-step Persiapan beres.
    Route::post('/waspang/projects/{project}/persiapan-instalasi/finish', [WaspangController::class, 'finishPersiapanInstalasi'])
        ->name('waspang.persiapan-instalasi.finish');

    // Kronologi universal (semua step)
    Route::post('/waspang/projects/{project}/kronologi', [WaspangController::class, 'storeKronologi'])
        ->name('waspang.kronologi.store');
});

/*
|--------------------------------------------------------------------------
| MASTER DESIGNATOR / PACKAGE / PRICE (role: admin, superadmin, super_tif --
| OFFICER DI-EXCLUDE, sesuai migration seed_officer_role "... KECUALI Master
| Designator ..." + nol link di sidebar officer)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,superadmin,super_tif'])->group(function () {

    Route::get('/designators', [DesignatorController::class, 'index'])
        ->name('designators.index');

    Route::post('/designators/store', [DesignatorController::class, 'store'])
        ->name('designators.store');

    Route::put('/designators/update/{id}', [DesignatorController::class, 'update'])
        ->name('designators.update');

    Route::delete('/designators/delete/{id}', [DesignatorController::class, 'destroy'])
        ->name('designators.destroy');

    Route::post('/designators/import', [DesignatorController::class, 'import'])
        ->name('designators.import');

    Route::patch('/designators/{id}/toggle-finishing', [DesignatorController::class, 'toggleFinishing'])
        ->name('designators.toggle-finishing');

    Route::get('/packages', [PackageController::class, 'index'])
        ->name('packages.index');

    Route::post('/packages', [PackageController::class, 'store'])
        ->name('packages.store');

    Route::put('/packages/update/{id}', [PackageController::class, 'update'])
        ->name('packages.update');

    Route::delete('/packages/{id}', [PackageController::class, 'destroy'])
        ->name('packages.destroy');

    Route::post('/packages/import', [PackageController::class, 'import'])
        ->name('packages.import');

    Route::get('/designator-prices', [DesignatorPriceController::class, 'index'])
        ->name('designator-prices.index');

    Route::post('/designator-prices', [DesignatorPriceController::class, 'store'])
        ->name('designator-prices.store');

    Route::put('/designator-prices/update/{id}', [DesignatorPriceController::class, 'update'])
        ->name('designator-prices.update');

    Route::delete('/designator-prices/{id}', [DesignatorPriceController::class, 'destroy'])
        ->name('designator-prices.destroy');

    Route::post('/designator-prices/import', [DesignatorPriceController::class, 'import'])
        ->name('designator-prices.import');
});

/*
|--------------------------------------------------------------------------
| MASTER TAHAPAN, KENDALA & PERIZINAN (Stage 3 refactor flow 11-tahap)
| -- role: superadmin SAJA (dikonfirmasi pemilik project 2026-09-08: menu
| ini khusus superadmin, admin/super_tif/officer TIDAK perlu akses).
| Awalnya sempat disatukan ke grup Master Designator (admin,superadmin,
| super_tif), lalu dipisah ke grup sendiri setelah dikonfirmasi lebih
| sempit dari itu.
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:superadmin'])->group(function () {

    Route::get('/admin/project-stages', [ProjectStageController::class, 'index'])
        ->name('admin.project-stages.index');

    Route::post('/admin/project-stages', [ProjectStageController::class, 'store'])
        ->name('admin.project-stages.store');

    Route::put('/admin/project-stages/{id}', [ProjectStageController::class, 'update'])
        ->name('admin.project-stages.update');

    Route::patch('/admin/project-stages/{id}/toggle-active', [ProjectStageController::class, 'toggleActive'])
        ->name('admin.project-stages.toggle-active');

    Route::delete('/admin/project-stages/{id}', [ProjectStageController::class, 'destroy'])
        ->name('admin.project-stages.destroy');

    Route::get('/admin/kendala-categories', [KendalaCategoryController::class, 'index'])
        ->name('admin.kendala-categories.index');

    Route::post('/admin/kendala-categories', [KendalaCategoryController::class, 'store'])
        ->name('admin.kendala-categories.store');

    Route::put('/admin/kendala-categories/{id}', [KendalaCategoryController::class, 'update'])
        ->name('admin.kendala-categories.update');

    Route::patch('/admin/kendala-categories/{id}/toggle-active', [KendalaCategoryController::class, 'toggleActive'])
        ->name('admin.kendala-categories.toggle-active');

    Route::delete('/admin/kendala-categories/{id}', [KendalaCategoryController::class, 'destroy'])
        ->name('admin.kendala-categories.destroy');

    Route::get('/admin/permit-categories', [PermitCategoryController::class, 'index'])
        ->name('admin.permit-categories.index');

    Route::post('/admin/permit-categories', [PermitCategoryController::class, 'store'])
        ->name('admin.permit-categories.store');

    Route::put('/admin/permit-categories/{id}', [PermitCategoryController::class, 'update'])
        ->name('admin.permit-categories.update');

    Route::patch('/admin/permit-categories/{id}/toggle-active', [PermitCategoryController::class, 'toggleActive'])
        ->name('admin.permit-categories.toggle-active');

    Route::delete('/admin/permit-categories/{id}', [PermitCategoryController::class, 'destroy'])
        ->name('admin.permit-categories.destroy');
});

/*
|--------------------------------------------------------------------------
| USER MANAGEMENT (role: superadmin, officer -- PERSIS sama dengan guard
| yang SUDAH ADA di UserManagementController::ensureSuperAdminOrOfficer())
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:superadmin,officer'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');

    Route::put('/admin/users/{id}', [UserManagementController::class, 'update'])
        ->name('admin.users.update');

    Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');

    Route::post('/admin/users/{user}/activate', [App\Http\Controllers\UserManagementController::class, 'activate'])->name('admin.users.activate');

    Route::post('/users/import', [UserManagementController::class, 'importCsv'])
        ->name('admin.users.import');
});

// Reset password ke default (= username) & hapus permanen -- keduanya
// superadmin-only (dijaga lagi di controller lewat ensureSuperAdmin()).
Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::post('/admin/users/{id}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->name('admin.users.reset-password');

    Route::delete('/admin/users/{id}/force-delete', [UserManagementController::class, 'forceDelete'])
        ->name('admin.users.force-delete');
});

/*
|--------------------------------------------------------------------------
| IMPORT PID & BOQ (role: admin, superadmin, super_tif -- OFFICER
| DI-EXCLUDE, sesuai migration seed_officer_role "... KECUALI ... Bulk
| Import Data ...")
|--------------------------------------------------------------------------
*/
Route::prefix('admin/import')
    ->middleware(['auth', 'role:admin,superadmin,super_tif'])
    ->group(function () {

    Route::get('/pid', [ImportController::class, 'pidIndex'])
        ->name('admin.import.pid');

    Route::post('/pid', [ImportController::class, 'importPid'])
        ->name('admin.import.pid.upload');

    Route::get('/admin/import/lop', [ImportController::class, 'lopIndex'])
        ->name('admin.import.lop');

    Route::post('/admin/import/lop', [ImportController::class, 'importLop'])
        ->name('admin.import.lop.upload');

    Route::get('/admin/import/lop/mapping', [ImportController::class, 'mappingIndex'])
        ->name('admin.import.lop.mapping');

    Route::post('/admin/import/lop/mapping/{id}', [ImportController::class, 'saveMapping'])
        ->name('admin.import.lop.mapping.save');

    Route::post('/admin/import/lop/mapping/{id}/reset', [ImportController::class, 'resetMapping'])
        ->name('admin.import.lop.mapping.reset');

    Route::put('/admin/import/pid/{project}/update', [ImportController::class, 'updatePid'])
        ->name('admin.import.pid.update');

    Route::delete('/admin/import/pid/{project}/delete', [ImportController::class, 'destroyPid'])
        ->name('admin.import.pid.delete');

    Route::get('/admin/import/pid/template', [ImportController::class, 'downloadPidTemplate'])
        ->name('admin.import.pid.template');

    // Polling status & download error report import PID (dipindah ke sini,
    // lihat catatan di bagian bawah file -- sebelumnya route ini ada DUA
    // KALI: sekali di sini via prefix admin/import (dengan auth), sekali
    // lagi di luar semua group TANPA middleware apapun. Duplikat GET/POST
    // '/admin/import/pid' & '/admin/import/boq' yang benar-benar identik
    // sudah dihapus (dead code, ke-shadow oleh yang di sini); 4 route unik
    // yang cuma ada di versi tanpa-auth itu (status/{uuid} & errors
    // download) DIPINDAH ke sini supaya ikut ter-autentikasi+ter-role-gate.
    Route::get('/admin/import/pid/status/{uuid}', [ImportController::class, 'importPidStatus'])
        ->name('admin.import.pid.status');

    Route::get('/admin/import/pid/errors/{uuid}/download', [ImportController::class, 'downloadPidImportErrors'])
        ->name('admin.import.pid.errors.download');

    Route::get('/admin/import/boq', [ImportController::class, 'boqIndex'])
        ->name('admin.import.boq');

    Route::post('/admin/import/boq/upload', [ImportController::class, 'importBoq'])
        ->name('admin.import.boq.upload');

    Route::post('/admin/import/boq', [ImportController::class, 'importBoq'])
        ->name('admin.import.boq.upload.alt');

    Route::get('/admin/import/boq/status/{uuid}', [ImportController::class, 'importBoqStatus'])
        ->name('admin.import.boq.status');

    Route::get('/admin/import/boq/errors/{uuid}/download', [ImportController::class, 'downloadBoqImportErrors'])
        ->name('admin.import.boq.errors.download');

    Route::get('/admin/import/boq/template', [ImportController::class, 'downloadBoqTemplate'])
        ->name('admin.import.boq.template');
});

/*
|--------------------------------------------------------------------------
| DATA PID & DATA BOQ (role: admin, superadmin, super_tif, officer --
| FIX 2026-09-08: sebelumnya digabung 1 role-gate dengan Bulk Import Data
| di atas (admin,superadmin,super_tif TANPA officer), tapi ternyata sidebar
| officer sendiri MEMANG me-link ke admin.data-pid/admin.data-boq (di bawah
| label "Master Data") -- beda dari admin.import.pid/admin.import.boq (form
| upload) yang memang di-exclude untuk officer per migration
| seed_officer_role. Dipisah jadi role-gate sendiri di sini supaya officer
| bisa lihat data yang sudah diimport tanpa perlu akses ke upload/bulk
| import-nya.
|--------------------------------------------------------------------------
*/
Route::prefix('admin/import')
    ->middleware(['auth', 'role:admin,superadmin,super_tif,officer'])
    ->group(function () {

    Route::get('/admin/data-pid', [ImportController::class, 'dataPid'])
        ->name('admin.data-pid');

    Route::get('/admin/data-pid/export', [ImportController::class, 'exportPid'])
        ->name('admin.data-pid.export');

    Route::get('/admin/data-boq', [ImportController::class, 'dataBoq'])
        ->name('admin.data-boq');

    Route::get('/admin/data-boq/export', [ImportController::class, 'exportBoq'])
        ->name('admin.data-boq.export');
});

/*
|--------------------------------------------------------------------------
| DASHBOARD PM (role: pm, tif -- DashboardController::index() HANYA
| redirect kedua role ini ke sini; role admin-ish dapat dashboard admin yang
| BEDA. "TIF memakai menu & dashboard yang sama persis dengan PM" per
| komentar asli DashboardController & ProgramController.)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pm,tif'])->prefix('pm')->name('pm.')->group(function () {

    // 1. Dashboard PM (Ini route yang memicu error saat login tadi)
    Route::get('/dashboard', [DashboardPmController::class, 'index'])->name('dashboard');

    // 1b. Endpoint JSON untuk modal "klik angka pada tabel matrix" Dashboard PM
    Route::get('/dashboard/matrix-detail', [DashboardPmController::class, 'matrixDetail'])->name('dashboard.matrix-detail');

    // 2. Rekap Progress LOP
    Route::get('/rekap-progress', [DashboardPmController::class, 'rekap'])->name('rekap.progress');

    // ---------------------------------------------------------
    //
    // MAP MONITORING CLUSTER PM
    // ---------------------------------------------------------
    // Route Fitur Peta
    Route::get('/map-monitoring', [DashboardPmController::class, 'map'])->name('map.monitoring');
    Route::get('/api/map-data', [DashboardPmController::class, 'mapData'])->name('api.map.data');

    Route::get('/kinerja-waspang', function() {
        return 'Halaman Kinerja Waspang (Under Construction)';
    })->name('kinerja.waspang');

    Route::get('/assign-waspang', function() {
        return 'Halaman Assign Waspang (Under Construction)';
    })->name('assign.waspang');

    // Route untuk Rekap Progress PM (Menggunakan parameter query ?program=...)
    Route::get('/rekap-progress', [DashboardPmController::class, 'rekapProgress'])->name('rekap_progress');

    Route::get('/waspang-performance', [App\Http\Controllers\DashboardPmController::class, 'waspangPerformance'])
        ->name('waspang.performance');
});

/*
|--------------------------------------------------------------------------
| ROLE TEKNISI (PT2)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:teknisi'])->prefix('teknisi/pt2')->name('teknisi.pt2.')->group(function () {

    // Dashboard & Inbox
    Route::get('/dashboard', [\App\Http\Controllers\TeknisiPt2Controller::class, 'index'])->name('index');
    Route::get('/inbox', [\App\Http\Controllers\TeknisiPt2Controller::class, 'inbox'])->name('inbox');

    // Step 1: Survey & Pilih Mode
    Route::get('/survey/{lop_id}', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step1'])->name('step1');
    Route::post('/survey/{lop_id}', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep1'])->name('storeStep1');
    Route::get('/survey/{lop_id}/eviden', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step1Eviden'])->name('step1Eviden');
    Route::post('/survey/{lop_id}/eviden', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep1Eviden'])->name('storeStep1Eviden');

    // ==========================================
    // MANAJEMEN EVIDEN (HAPUS & REPLACE)
    // ==========================================
    Route::delete('/eviden/{id}', [\App\Http\Controllers\TeknisiPt2Controller::class, 'deleteEvidence'])->name('deleteEvidence');
    Route::post('/eviden/{id}/replace', [\App\Http\Controllers\TeknisiPt2Controller::class, 'replaceEvidence'])->name('replaceEvidence'); // <-- TAMBAHAN BARU

    // Step 2: Eviden Progress Instalasi
    Route::get('/survey/{lop_id}/step2', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step2Eviden'])->name('step2Eviden');
    Route::post('/survey/{lop_id}/step2', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep2Eviden'])->name('storeStep2Eviden');

    // Step 3: Eviden Finishing (Redaman ODP)
    Route::get('/survey/{lop_id}/step3', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step3Eviden'])->name('step3Eviden');
    Route::post('/survey/{lop_id}/step3', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep3Eviden'])->name('storeStep3Eviden');

    // Step 4: Dismantle Material
    Route::get('/survey/{lop_id}/step4', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step4Eviden'])->name('step4Eviden');
    Route::post('/survey/{lop_id}/step4', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep4Eviden'])->name('storeStep4Eviden');

    // Step 5: Mancore & Submit Approval
    Route::get('/survey/{lop_id}/step5', [\App\Http\Controllers\TeknisiPt2Controller::class, 'step5'])->name('step5');
    Route::post('/survey/{lop_id}/step5', [\App\Http\Controllers\TeknisiPt2Controller::class, 'storeStep5'])->name('storeStep5');
});

// PROFILE TEKNISI
Route::middleware(['auth', 'role:teknisi'])->group(function () {
    Route::get('/teknisi/profil', function () {
        return view('teknisi.profile');
    })->name('teknisi.profil');
});

/*
|--------------------------------------------------------------------------
| ROUTE KHUSUS APPROVAL PT2 OLEH ADMIN (role: admin, superadmin, super_tif,
| officer -- dikonfirmasi lewat grep sidebar: admin.pt2.* dilink dari
| sidebar admin, officer, DAN super_tif -- beda dengan Approval Eviden PT3
| yang officer-nya di-exclude)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/pt2')->name('admin.pt2.')->middleware(['auth', 'role:admin,superadmin,super_tif,officer'])->group(function () {

    // List & Detail Review
    Route::get('/approval', [\App\Http\Controllers\AdminPt2Controller::class, 'approvalList'])->name('approval');
    Route::get('/review/{id}', [\App\Http\Controllers\AdminPt2Controller::class, 'review'])->name('review');

    // Review per Step
    Route::get('/{id}/instalasi', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewInstalasi'])->name('instalasi');
    Route::get('/{id}/redaman', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewRedaman'])->name('redaman');
    Route::get('/{id}/dismantle', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewDismantle'])->name('dismantle');
    Route::get('/{id}/mancore', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewMancore'])->name('mancore');

    // BAUT (Berita Acara Uji Terima) - generate dokumen per LOP
    Route::get('/baut', [\App\Http\Controllers\BautController::class, 'index'])->name('baut.index');
    Route::get('/baut/{bautId}', [\App\Http\Controllers\BautController::class, 'show'])->name('baut.show');
    Route::get('/baut/{bautId}/download', [\App\Http\Controllers\BautController::class, 'download'])->name('baut.download');
    Route::get('/{id}/baut', [\App\Http\Controllers\BautController::class, 'editor'])->name('baut.editor');
    Route::post('/{id}/baut/draft', [\App\Http\Controllers\BautController::class, 'saveDraft'])->name('baut.saveDraft');
    Route::post('/{id}/baut/generate', [\App\Http\Controllers\BautController::class, 'generate'])->name('baut.generate');

    // LACT (Laporan Commissioning Test) - generate dokumen per LOP, lanjutan
    // dari BAUT (baru bisa digenerate setelah BAUT LOP ybs berstatus final)
    Route::get('/lact', [\App\Http\Controllers\LactController::class, 'index'])->name('lact.index');
    Route::get('/lact/{lactId}', [\App\Http\Controllers\LactController::class, 'show'])->name('lact.show');
    Route::get('/lact/{lactId}/download', [\App\Http\Controllers\LactController::class, 'download'])->name('lact.download');
    Route::get('/{id}/lact', [\App\Http\Controllers\LactController::class, 'editor'])->name('lact.editor');
    Route::post('/{id}/lact/draft', [\App\Http\Controllers\LactController::class, 'saveDraft'])->name('lact.saveDraft');
    Route::post('/{id}/lact/generate', [\App\Http\Controllers\LactController::class, 'generate'])->name('lact.generate');

    // Aksi Form Survey
    Route::post('/survey/{id}/approve', [\App\Http\Controllers\AdminPt2Controller::class, 'approveSurvey'])->name('survey.approve');
    Route::post('/survey/{id}/reject', [\App\Http\Controllers\AdminPt2Controller::class, 'rejectSurvey'])->name('survey.reject');
    Route::post('/survey/{id}/reset', [\App\Http\Controllers\AdminPt2Controller::class, 'resetSurvey'])->name('survey.reset');

    // Aksi Form Eviden PT2
    Route::post('/eviden/{id}/approve', [\App\Http\Controllers\AdminPt2Controller::class, 'approveEvidencePt2'])->name('evidence.approve');
    Route::post('/eviden/{id}/reject', [\App\Http\Controllers\AdminPt2Controller::class, 'rejectEvidencePt2'])->name('evidence.reject');
    Route::post('/eviden/{id}/reset', [\App\Http\Controllers\AdminPt2Controller::class, 'resetEvidencePt2'])->name('evidence.reset');
    Route::post('/eviden/bulk-approve', [\App\Http\Controllers\AdminPt2Controller::class, 'bulkApprovePt2'])->name('evidence.bulk-approve');

    // Aksi Kirim ke SDI
    Route::post('/{id}/send-to-sdi', [\App\Http\Controllers\AdminPt2Controller::class, 'sendToSdi'])->name('sendToSdi');
});

/*
|--------------------------------------------------------------------------
| ROLE SDI
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:sdi'])->prefix('sdi')->name('sdi.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SdiController::class, 'index'])->name('index');

    // Route untuk mengeksekusi go-live per LOP PT 2
    Route::post('/golive/{id}', [\App\Http\Controllers\SdiController::class, 'submitGolive'])->name('golive.store');

    // Jika menggunakan parameter {id}
    Route::post('/admin/pt2/{id}/send-to-sdi', [AdminPt2Controller::class, 'sendToSdi'])->name('admin.pt2.sendToSdi');
});

// FIX (2026-09-08): route ini sebelumnya TIDAK berada di dalam group manapun
// -- benar-benar tanpa 'auth' sama sekali. Sekarang digembok auth+role:sdi
// (sama seperti prefix sdi.* di atas, konsisten dengan aksi golive lainnya).
Route::middleware(['auth', 'role:sdi'])->group(function () {
    Route::post('/sdi/pt2/golive/{lop_id}', [App\Http\Controllers\SdiController::class, 'submitGolive'])->name('sdi.eksekusi.golive');
});

/*
|--------------------------------------------------------------------------
| ROLE SDI SURVEYOR - Survey Lapangan (Tagging Tiang, Catuan & Rute Kabel)
|--------------------------------------------------------------------------
| Role: sdi_surveyor, admin, sdi, waspang, superadmin, super_tif, officer --
| PERSIS sama dengan SurveyorController::ALLOWED_ROLES (guard yang sudah ada
| di controller; 'officer' ditambahkan 2026-09-08 setelah ditemukan sidebar
| officer link ke admin.site-surveys.index tapi constant-nya belum diupdate
| saat role officer dibuat). pm/tif/teknisi TIDAK termasuk (konsisten dgn
| guard existing & nol link di sidebar mereka).
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:sdi_surveyor,admin,sdi,waspang,superadmin,super_tif,officer'])->prefix('surveyor')->name('surveyor.')->group(function () {
    Route::get('/', [SurveyorController::class, 'index'])->name('index');
    Route::get('/create', [SurveyorController::class, 'create'])->name('create');
    Route::get('/profile', [SurveyorController::class, 'profile'])->name('profile');
    Route::post('/', [SurveyorController::class, 'store'])->name('store');
    Route::get('/{id}', [SurveyorController::class, 'show'])->name('show');
    Route::delete('/{id}', [SurveyorController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/complete', [SurveyorController::class, 'complete'])->name('complete');
    Route::get('/{id}/kml', [SurveyorController::class, 'downloadKml'])->name('kml');
    Route::post('/{id}/ending-site', [SurveyorController::class, 'setEndingSite'])->name('ending-site.store');

    Route::post('/{id}/points', [SurveyorController::class, 'storePoint'])->name('points.store');
    Route::put('/points/{pointId}', [SurveyorController::class, 'updatePoint'])->name('points.update');
    Route::delete('/points/{pointId}', [SurveyorController::class, 'destroyPoint'])->name('points.destroy');

    Route::post('/{id}/routes', [SurveyorController::class, 'storeRoute'])->name('routes.store');
    Route::put('/routes/{routeId}', [SurveyorController::class, 'updateRoute'])->name('routes.update');
    Route::delete('/routes/{routeId}', [SurveyorController::class, 'destroyRoute'])->name('routes.destroy');
});

/*
|--------------------------------------------------------------------------
| GIS TO CAD GENERATOR - KML/KMZ atau Data Survey Lapangan -> DXF AutoCAD
|--------------------------------------------------------------------------
| Role: sama persis dengan surveyor.* di atas -- GisCadController::
| ALLOWED_ROLES (guard yang sudah ada) identik dengan SurveyorController.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:sdi_surveyor,admin,sdi,waspang,superadmin,super_tif,officer'])->prefix('gis-cad')->name('gis-cad.')->group(function () {
    Route::get('/', [GisCadController::class, 'index'])->name('index');
    Route::get('/create', [GisCadController::class, 'create'])->name('create');

    Route::post('/upload', [GisCadController::class, 'storeUpload'])->name('upload');
    Route::post('/from-survey/{surveyId}', [GisCadController::class, 'storeFromSurvey'])->name('from-survey');

    Route::get('/{uuid}/review', [GisCadController::class, 'review'])->name('review');
    Route::post('/{uuid}/review', [GisCadController::class, 'updateReview'])->name('review.update');
    Route::post('/{uuid}/confirm', [GisCadController::class, 'confirm'])->name('confirm');

    Route::get('/{uuid}', [GisCadController::class, 'show'])->name('show');
    Route::get('/{uuid}/status', [GisCadController::class, 'status'])->name('status');
    Route::get('/{uuid}/download/dxf', [GisCadController::class, 'downloadDxf'])->name('download.dxf');
    Route::get('/{uuid}/download/bom', [GisCadController::class, 'downloadBom'])->name('download.bom');
    Route::delete('/{uuid}', [GisCadController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| HASIL SURVEY LAPANGAN - TAMPILAN ADMIN / SDI (DESKTOP)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:sdi_surveyor,admin,sdi,waspang,superadmin,super_tif,officer'])->prefix('admin/site-surveys')->name('admin.site-surveys.')->group(function () {
    Route::get('/', [SurveyorController::class, 'adminIndex'])->name('index');
    Route::get('/{id}', [SurveyorController::class, 'adminShow'])->name('show');
});

/*
|--------------------------------------------------------------------------
| GIS TO CAD GENERATOR - TAMPILAN ADMIN (DESKTOP)
|--------------------------------------------------------------------------
| Route & controller SAMA PERSIS dengan grup 'gis-cad.*' di atas (mobile) -
| lihat GisCadController::isAdminContext()/viewName()/routeName(). Yang beda
| cuma prefix URL & nama route, supaya admin dapat tampilan desktop sendiri.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:sdi_surveyor,admin,sdi,waspang,superadmin,super_tif,officer'])->prefix('admin/gis-cad')->name('admin.gis-cad.')->group(function () {
    Route::get('/', [GisCadController::class, 'index'])->name('index');
    Route::get('/create', [GisCadController::class, 'create'])->name('create');

    Route::post('/upload', [GisCadController::class, 'storeUpload'])->name('upload');
    Route::post('/from-survey/{surveyId}', [GisCadController::class, 'storeFromSurvey'])->name('from-survey');

    Route::get('/{uuid}/review', [GisCadController::class, 'review'])->name('review');
    Route::post('/{uuid}/review', [GisCadController::class, 'updateReview'])->name('review.update');
    Route::post('/{uuid}/confirm', [GisCadController::class, 'confirm'])->name('confirm');

    Route::get('/{uuid}', [GisCadController::class, 'show'])->name('show');
    Route::get('/{uuid}/status', [GisCadController::class, 'status'])->name('status');
    Route::get('/{uuid}/download/dxf', [GisCadController::class, 'downloadDxf'])->name('download.dxf');
    Route::get('/{uuid}/download/bom', [GisCadController::class, 'downloadBom'])->name('download.bom');
    Route::delete('/{uuid}', [GisCadController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| ROUTE PT2 YANG SEBELUMNYA BENAR-BENAR TANPA MIDDLEWARE (FIX 2026-09-08)
|--------------------------------------------------------------------------
| Sebelumnya route-route ini berada di luar SEMUA group -- tidak ada 'auth'
| sama sekali, siapapun (bahkan yang belum login) bisa mengaksesnya kalau
| tahu URL-nya. Sekarang digembok sama seperti admin.pt2.* (role: admin,
| superadmin, super_tif, officer). URI, nama route, dan controller/method
| TIDAK diubah sama sekali -- cuma dibungkus middleware.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,superadmin,super_tif,officer'])->group(function () {
    // Route Upload PID & BOQ Khusus PT 2 (Arahkan ke fungsi yang sama di controller)
    Route::post('/import/pt2/upload-pid', [App\Http\Controllers\ImportController::class, 'importPid'])->name('admin.import.pt2.upload');
    Route::post('/import/pt2/upload-boq', [App\Http\Controllers\ImportController::class, 'importBoq'])->name('admin.import.pt2.upload-boq');

    // ROUTE KHUSUS ASSIGNMENT PT 2
    Route::post('/pt2-assignments/assign', [Pt2AssignmentController::class, 'assignTeknisi'])->name('admin.pt2.assign');
    Route::delete('/pt2-assignments/{pt2_lop_id}/remove', [Pt2AssignmentController::class, 'removeAssign'])->name('admin.pt2.remove_assign');

    // Route Menu Program PT 2
    Route::get('/pt2', [AdminPt2Controller::class, 'index'])->name('admin.pt2.index');
    Route::get('/pt2/tracking/{lop_id}', [\App\Http\Controllers\AdminPt2Controller::class, 'tracking'])->name('admin.pt2.tracking');
    Route::delete('/pt2/destroy-lop/{lop_id}', [\App\Http\Controllers\AdminPt2Controller::class, 'destroyLop'])->name('admin.pt2.destroyLop');

    // Route Assignment PT 2
    Route::post('/pt2/assignments/store', [Pt2AssignmentController::class, 'assignTeknisi'])->name('admin.pt2.assign.store');
    Route::delete('/pt2/assignments/remove/{pt2_lop_id}', [Pt2AssignmentController::class, 'removeAssign'])->name('admin.pt2.assign.remove');
});

/*
|--------------------------------------------------------------------------
| RUTE PROGRAM KHUSUS (Kecuali PT 2)
|--------------------------------------------------------------------------
| Role: pm, tif, admin, superadmin, super_tif, officer -- dikonfirmasi lewat
| grep sidebar: program.* dilink dari sidebar pm, admin, officer, DAN
| super_tif. Exclude project Konstruksi Eksternal untuk role super_tif/tif
| SUDAH dijaga sendiri di ProgramController (baris 171 & 357), jadi tidak
| perlu diduplikasi di middleware ini.
|
| FIX (2026-09-08): group ini sebelumnya TIDAK punya 'auth' SAMA SEKALI --
| artinya seluruh halaman & export program (OSP/Node B/HEM/OLO/Konstruksi
| Eksternal) bisa diakses publik tanpa login. Sekarang digembok auth+role.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pm,tif,admin,superadmin,super_tif,officer'])->prefix('program')->name('program.')->group(function () {
    Route::get('/osp', [ProgramController::class, 'osp'])->name('osp');
    Route::get('/node-b', [ProgramController::class, 'nodeb'])->name('nodeb');
    Route::get('/hem', [ProgramController::class, 'hem'])->name('hem');
    Route::get('/olo', [ProgramController::class, 'olo'])->name('olo');
    Route::get('/konstruksi-eksternal', [ProgramController::class, 'konstruk'])->name('konstruk');

    // Download Data LOP per program (menu Project ID role TIF/PM) -- mengikuti
    // filter yang aktif (search/region/branch/status_progress), atau seluruh
    // data kalau tidak ada filter aktif. Lihat ProgramController::exportProgramLop().
    Route::get('/osp/export', [ProgramController::class, 'exportOsp'])->name('osp.export');
    Route::get('/node-b/export', [ProgramController::class, 'exportNodeb'])->name('nodeb.export');
    Route::get('/hem/export', [ProgramController::class, 'exportHem'])->name('hem.export');
    Route::get('/olo/export', [ProgramController::class, 'exportOlo'])->name('olo.export');
    Route::get('/konstruksi-eksternal/export', [ProgramController::class, 'exportKonstruk'])->name('konstruk.export');
});

require __DIR__.'/auth.php';
