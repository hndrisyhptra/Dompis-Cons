<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DesignatorController;
use App\Http\Controllers\WaspangController;


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
    /*
    |--------------------------------------------------------------------------
    | PROJECT / LOP & BOQ
    |--------------------------------------------------------------------------
    */

    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index');

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
    | DESIGNATOR
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

    });

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';