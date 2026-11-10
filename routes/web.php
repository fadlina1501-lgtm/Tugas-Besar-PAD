<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\MaintenanceTicketController;
use App\Http\Controllers\LifeCycleCostController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman utama
Route::get('/', fn () => Auth::check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
);

// Login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.submit');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Halaman yang membutuhkan login
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Manajemen pengguna
    Route::middleware('role:manajemen')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])
            ->name('users.index');

        Route::post('/users', [UserManagementController::class, 'store'])
            ->name('users.store');

        Route::put('/users/{user}', [UserManagementController::class, 'update'])
            ->name('users.update');
    });

    // Master data
    Route::middleware('role:sarpras,manajemen')->group(function () {
        Route::get('/master-data', [MasterDataController::class, 'index'])
            ->name('master-data.index');

        Route::post('/master-data/categories', [MasterDataController::class, 'storeCategory'])
            ->name('master-data.categories.store');

        Route::delete('/master-data/categories/{category}', [MasterDataController::class, 'destroyCategory'])
            ->name('master-data.categories.destroy');

        Route::post('/master-data/locations', [MasterDataController::class, 'storeLocation'])
            ->name('master-data.locations.store');

        Route::delete('/master-data/locations/{location}', [MasterDataController::class, 'destroyLocation'])
            ->name('master-data.locations.destroy');
    });

    // Aset
    Route::get('/assets', [AssetController::class, 'index'])
        ->name('assets.index');

    Route::middleware('role:sarpras,manajemen')->group(function () {
        Route::get('/assets/create', [AssetController::class, 'create'])
            ->name('assets.create');

        Route::post('/assets', [AssetController::class, 'store'])
            ->name('assets.store');

        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])
            ->name('assets.edit');

        Route::put('/assets/{asset}', [AssetController::class, 'update'])
            ->name('assets.update');

        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
            ->name('assets.destroy');

        // Suku cadang
        Route::post('/spare-parts', [SparePartController::class, 'store'])
            ->name('spare-parts.store');

        Route::put('/spare-parts/{sparePart}', [SparePartController::class, 'update'])
            ->name('spare-parts.update');
    });

    // Life-Cycle Cost
    Route::get(
        '/life-cycle-cost',
        [LifeCycleCostController::class, 'index']
    )->middleware('role:sarpras,manajemen')
    ->name('life-cycle-cost.index');

    // Laporan kerusakan
    Route::get('/reports', [DamageReportController::class, 'index'])
        ->middleware('role:pengguna,sarpras,manajemen')
        ->name('reports.index');

    // Verifikasi laporan dan pengelolaan tiket
    Route::middleware('role:sarpras')->group(function () {
        Route::post('/reports/{report}/verify', [DamageReportController::class, 'verify'])
            ->name('reports.verify');

        // Untuk laporan lama yang belum memiliki tiket
        Route::post('/reports/{report}/create-ticket', [MaintenanceTicketController::class, 'storeFromReport'])
            ->name('reports.ticket');

        Route::post('/tickets/{ticket}/work-orders', [MaintenanceTicketController::class, 'createWorkOrder'])
            ->name('tickets.work-orders.store');
    });

    // Pengguna fasilitas
    Route::middleware('role:pengguna')->group(function () {
        Route::get('/reports/create', [DamageReportController::class, 'create'])
            ->name('reports.create');

        Route::post('/reports', [DamageReportController::class, 'store'])
            ->name('reports.store');

        Route::put('/reports/{report}/revise', [DamageReportController::class, 'revise'])
            ->name('reports.revise');
    });

    // Tiket maintenance
    Route::get('/tickets', [MaintenanceTicketController::class, 'index'])
        ->middleware('role:sarpras,manajemen')
        ->name('tickets.index');

    // Work order
    Route::get('/work-orders', [WorkOrderController::class, 'index'])
        ->middleware('role:sarpras,teknisi,manajemen')
        ->name('work-orders.index');

    Route::post('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
        ->middleware('role:sarpras,teknisi')
        ->name('work-orders.status');

    Route::post('/work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart'])
        ->middleware('role:sarpras,teknisi')
        ->name('work-orders.parts');

    // Suku cadang
    Route::get('/spare-parts', [SparePartController::class, 'index'])
        ->middleware('role:sarpras,manajemen')
        ->name('spare-parts.index');
});