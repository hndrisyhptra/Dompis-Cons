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

    Route::get('/admin/history', [DashboardController::class, 'adminHistory'])
        ->name('admin.history');

    Route::get('/admin/projects/{id}', [DashboardController::class, 'show'])
        ->name('admin.projects.show');
        
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
| AUTH
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';