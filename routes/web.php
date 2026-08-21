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
| AUTH REQUIRED
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/admin/map-monitoring', [DashboardController::class, 'mapMonitoring'])
        ->middleware(['auth'])
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

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | DESIGNATOR | PACKAGE | PRICE
    |--------------------------------------------------------------------------
    */

    Route::get('/designators', [DesignatorController::class, 'index'])
        ->name('designators.index');

    Route::post('/designators/store', [DesignatorController::class, 'store'])
        ->name('designators.store');

    Route::put('/designators/update/{id}', [DesignatorController::class, 'update'])
        ->name('designators.update');

    Route::delete('/designators/delete/{id}', [DesignatorController::class, 'destroy'])
        ->name('designators.destroy');

    Route::post('/projects/boq/store', [ProjectController::class, 'storeBoq'])
        ->name('projects.boq.store');

    Route::post('/designators/import', [DesignatorController::class, 'import'])
        ->name('designators.import');

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

    Route::post('/users/import', [UserManagementController::class, 'import'])
        ->name('admin.users.import');

    Route::patch('/designators/{id}/toggle-finishing', [DesignatorController::class, 'toggleFinishing'])
        ->name('designators.toggle-finishing');
    

    /*
    |--------------------------------------------------------------------------
    | WASPANG MOBILE
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | APPROVAL FROM ADMIN
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/evidences/approval', [ProjectController::class, 'approvalIndex'])
    ->middleware(['auth'])
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

    Route::post('/admin/evidences/bulk-approve', [\App\Http\Controllers\ProjectController::class, 'bulkApprove'])
    ->name('admin.evidences.bulk-approve');


    //WASPANG MOBILE
    Route::get('/waspang/projects/{id}/pengukuran', [WaspangController::class, 'pengukuran'])
    ->name('waspang.projects.pengukuran');

    Route::get('/waspang/projects/{id}/finishing', [WaspangController::class, 'finishing'])
    ->name('waspang.projects.finishing');

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

    });

    Route::middleware(['auth'])->group(function () {
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users.index');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');

    Route::put('/admin/users/{id}', [UserManagementController::class, 'update'])
        ->name('admin.users.update');

    Route::delete('/admin/users/{id}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');
    

     Route::get('/admin/projects/{project}/tracking', [DashboardController::class, 'tracking'])
            ->name('admin.projects.tracking');
    
    Route::post('/admin/users/{user}/activate', [App\Http\Controllers\UserManagementController::class, 'activate'])->name('admin.users.activate');
});


/*
|--------------------------------------------------------------------------
| IMPORT PID
|--------------------------------------------------------------------------
*/
        Route::prefix('admin/import')
            ->middleware(['auth'])
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

        Route::get('/admin/data-pid', [ImportController::class, 'dataPid'])
            ->name('admin.data-pid');

        Route::get('/admin/import/boq', [ImportController::class, 'boqIndex'])
            ->name('admin.import.boq');

        Route::post('/admin/import/boq/upload', [ImportController::class, 'importBoq'])
            ->name('admin.import.boq.upload');

        Route::get('/admin/data-boq', [ImportController::class, 'dataBoq'])
            ->name('admin.data-boq');

        Route::get('/admin/import/boq/template', [ImportController::class, 'downloadBoqTemplate'])
            ->name('admin.import.boq.template');

    });

    /*
|--------------------------------------------------------------------------
| DASHBOARD PM REAL ROLE PM
|--------------------------------------------------------------------------
*/

    Route::middleware(['auth'])->prefix('pm')->name('pm.')->group(function () {
    
    // 1. Dashboard PM (Ini route yang memicu error saat login tadi)
    Route::get('/dashboard', [DashboardPmController::class, 'index'])->name('dashboard');
    
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
Route::middleware(['auth'])->prefix('teknisi/pt2')->name('teknisi.pt2.')->group(function () {
    
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
Route::middleware(['auth'])->group(function () {
    Route::get('/teknisi/profil', function () {
        return view('teknisi.profile');
    })->name('teknisi.profil');
});
/*
|--------------------------------------------------------------------------
| ROUTE KHUSUS APPROVAL PT2 OLEH ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin/pt2')->name('admin.pt2.')->middleware(['auth'])->group(function () {
    
    // List & Detail Review
    Route::get('/approval', [\App\Http\Controllers\AdminPt2Controller::class, 'approvalList'])->name('approval');
    Route::get('/review/{id}', [\App\Http\Controllers\AdminPt2Controller::class, 'review'])->name('review');

    // Review per Step
    Route::get('/{id}/instalasi', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewInstalasi'])->name('instalasi');
    Route::get('/{id}/redaman', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewRedaman'])->name('redaman');
    Route::get('/{id}/dismantle', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewDismantle'])->name('dismantle');
    Route::get('/{id}/mancore', [\App\Http\Controllers\AdminPt2Controller::class, 'reviewMancore'])->name('mancore');

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
Route::middleware(['auth'])->prefix('sdi')->name('sdi.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\SdiController::class, 'index'])->name('index');
    
    // Route untuk mengeksekusi go-live per LOP PT 2
    Route::post('/golive/{id}', [\App\Http\Controllers\SdiController::class, 'submitGolive'])->name('golive.store');

    // Jika menggunakan parameter {id}
    Route::post('/admin/pt2/{id}/send-to-sdi', [AdminPt2Controller::class, 'sendToSdi'])->name('admin.pt2.sendToSdi');
});
Route::post('/sdi/pt2/golive/{lop_id}', [App\Http\Controllers\SdiController::class, 'submitGolive'])->name('sdi.eksekusi.golive');

/*
|--------------------------------------------------------------------------
| ROLE SDI SURVEYOR - Survey Lapangan (Tagging Tiang, Catuan & Rute Kabel)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('surveyor')->name('surveyor.')->group(function () {
    Route::get('/', [SurveyorController::class, 'index'])->name('index');
    Route::get('/create', [SurveyorController::class, 'create'])->name('create');
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
| HASIL SURVEY LAPANGAN - TAMPILAN ADMIN / SDI (DESKTOP)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin/site-surveys')->name('admin.site-surveys.')->group(function () {
    Route::get('/', [SurveyorController::class, 'adminIndex'])->name('index');
    Route::get('/{id}', [SurveyorController::class, 'adminShow'])->name('show');
});

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

// Rute Program Khusus (Kecuali PT 2)
Route::prefix('program')->name('program.')->group(function () {
    Route::get('/osp', [ProgramController::class, 'osp'])->name('osp');
    Route::get('/node-b', [ProgramController::class, 'nodeb'])->name('nodeb');
    Route::get('/hem', [ProgramController::class, 'hem'])->name('hem');
    Route::get('/olo', [ProgramController::class, 'olo'])->name('olo');
    Route::get('/konstruksi-eksternal', [ProgramController::class, 'konstruk'])->name('konstruk');
});

/*
|--------------------------------------------------------------------------
| ROUTE NEW ARSITEKTUR PID
|--------------------------------------------------------------------------
*/
Route::get(
    '/admin/import/pid',
    [ImportController::class, 'pidIndex']
)->name('admin.import.pid');

Route::post(
    '/admin/import/pid',
    [ImportController::class, 'importPid']
)->name('admin.import.pid.upload');

Route::get(
    '/admin/import/pid/status/{uuid}',
    [ImportController::class, 'importPidStatus']
)->name('admin.import.pid.status');

Route::get(
    '/admin/import/pid/errors/{uuid}/download',
    [ImportController::class, 'downloadPidImportErrors']
)->name('admin.import.pid.errors.download');

/*
|--------------------------------------------------------------------------
| ROUTE NEW ARSITEKTUR BOQ
|--------------------------------------------------------------------------
*/

Route::get(
    '/admin/import/boq',
    [ImportController::class, 'boqIndex']
)->name('admin.import.boq');

Route::post(
    '/admin/import/boq',
    [ImportController::class, 'importBoq']
)->name('admin.import.boq.upload');

Route::get(
    '/admin/import/boq/status/{uuid}',
    [ImportController::class, 'importBoqStatus']
)->name('admin.import.boq.status');

Route::get(
    '/admin/import/boq/errors/{uuid}/download',
    [ImportController::class, 'downloadBoqImportErrors']
)->name('admin.import.boq.errors.download');

require __DIR__.'/auth.php';