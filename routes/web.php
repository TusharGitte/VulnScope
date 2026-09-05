<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\FindingController;
use App\Http\Controllers\LoadTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReconController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ScopeController;
use App\Http\Controllers\TargetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/settings/password', [ProfileController::class, 'password'])->name('profile.password');

    Route::resource('projects', ProjectController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::middleware('project.access')->group(function () {
        Route::resource('projects', ProjectController::class)->only(['show', 'edit', 'update', 'destroy']);

        Route::prefix('projects/{project}')->group(function () {
            Route::resource('targets', TargetController::class);
            Route::get('scope', [ScopeController::class, 'edit'])->name('scope.edit');
            Route::post('scope', [ScopeController::class, 'store'])->name('scope.store');

            Route::middleware('step:1')->group(function () {
                Route::get('recon', [ReconController::class, 'show'])->name('recon.show');
                Route::post('recon/start', [ReconController::class, 'start'])->name('recon.start');
                Route::post('recon/{scanRun}/cancel', [ReconController::class, 'cancel'])->name('recon.cancel');
            });

            Route::middleware('step:2')->group(function () {
                Route::get('scan', [ScanController::class, 'show'])->name('scan.show');
                Route::post('scan/start', [ScanController::class, 'start'])->name('scan.start');
                Route::post('scan/{scanRun}/cancel', [ScanController::class, 'cancel'])->name('scan.cancel');
            });

            Route::middleware('step:3')->group(function () {
                Route::get('load-test', [LoadTestController::class, 'show'])->name('load-test.show');
                Route::post('load-test/start', [LoadTestController::class, 'start'])->name('load-test.start');
                Route::post('load-test/{loadTest}/stop', [LoadTestController::class, 'stop'])->name('load-test.stop');
            });

            Route::middleware('step:4')->group(function () {
                Route::resource('findings', FindingController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
                Route::post('findings/{finding}/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
                Route::delete('findings/{finding}/evidence/{evidence}', [EvidenceController::class, 'destroy'])->name('evidence.destroy');

                Route::get('report', [ReportController::class, 'show'])->name('report.show');
                Route::post('report/generate', [ReportController::class, 'generate'])->name('report.generate');
                Route::get('report/{report}/view', [ReportController::class, 'view'])->name('report.view');
                Route::get('report/{report}/download', [ReportController::class, 'download'])->name('report.download');
                Route::delete('report/{report}', [ReportController::class, 'destroy'])->name('report.destroy');
            });
        });
    });
});

require __DIR__ . '/auth_extra.php';
