<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\MaintenanceTicketController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Halaman awal diarahkan sesuai peran pengguna.
Route::get('/', function () {
    if (! Auth::check()) {
        return redirect()->route('login');
    }

    $route = match (Auth::user()->role) {
        'pengguna' => 'reports.index',
        'sarpras' => 'reports.index',
        'teknisi' => 'work-orders.index',
        'manajemen' => 'dashboard',
        default => 'login',
    };

    return redirect()->route($route);
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    // Pengguna Fasilitas dan Sarpras: pelaporan serta verifikasi kerusakan.
    Route::get('/reports', [DamageReportController::class, 'index'])
        ->middleware('role:pengguna,sarpras')
        ->name('reports.index');

    Route::middleware('role:pengguna')->group(function () {
        Route::get('/reports/create', [DamageReportController::class, 'create'])
            ->name('reports.create');
        Route::post('/reports', [DamageReportController::class, 'store'])
            ->name('reports.store');
        Route::put('/reports/{report}/revise', [DamageReportController::class, 'revise'])
            ->name('reports.revise');
    });

    // Sarpras: memeriksa laporan, memproses tiket, membuat WO, dan menetapkan teknisi.
    Route::middleware('role:sarpras')->group(function () {
        Route::post('/reports/{report}/verify', [DamageReportController::class, 'verify'])
            ->name('reports.verify');

        Route::get('/tickets', [MaintenanceTicketController::class, 'index'])
            ->name('tickets.index');
        Route::post('/tickets/{ticket}/work-orders', [MaintenanceTicketController::class, 'createWorkOrder'])
            ->name('tickets.work-orders.store');
    });

    // Teknisi: menerima work order, memulai perbaikan, mencatat progres dan biaya, lalu menyelesaikan.
    Route::middleware('role:teknisi')->group(function () {
        Route::get('/work-orders', [WorkOrderController::class, 'index'])
            ->name('work-orders.index');
        Route::post('/work-orders/{workOrder}/accept', [WorkOrderController::class, 'accept'])
            ->name('work-orders.accept');
        Route::post('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus'])
            ->name('work-orders.status');
        Route::post('/work-orders/{workOrder}/parts', [WorkOrderController::class, 'addPart'])
            ->name('work-orders.parts');
    });

    // Manajemen: melihat dashboard dan mengevaluasi kelayakan aset.
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:manajemen')
        ->name('dashboard');
});
