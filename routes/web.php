<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PegawaiPageController;
use App\Http\Controllers\AtasanPageController;
use App\Http\Controllers\LobbyPageController;
use App\Http\Controllers\PosPageController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\PimpinanPageController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes - SIKMA BPMP Gorontalo
|--------------------------------------------------------------------------
*/

// Root → redirect ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth (Laravel Breeze)
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login')->middleware('guest');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout')->middleware('auth');

// Pegawai
Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/dashboard', [PegawaiPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/scan', [PegawaiPageController::class, 'scan'])->name('scan');
    Route::get('/izin-dinas', [PegawaiPageController::class, 'izinDinas'])->name('izin-dinas');
    Route::get('/riwayat', [PegawaiPageController::class, 'riwayat'])->name('riwayat');
});

// Atasan
Route::middleware(['auth', 'role:atasan'])->prefix('atasan')->name('atasan.')->group(function () {
    Route::get('/izin-dinas', [AtasanPageController::class, 'izinDinas'])->name('izin-dinas');
});

// Lobby
Route::middleware(['auth', 'role:lobby'])->prefix('lobby')->name('lobby.')->group(function () {
    Route::get('/layar', [LobbyPageController::class, 'layar'])->name('layar');
});

// Pos
Route::middleware(['auth', 'role:pos'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/layar', [PosPageController::class, 'layar'])->name('layar');
});

// Admin
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/pegawai', [AdminPageController::class, 'pegawai'])->name('pegawai');
    Route::get('/pegawai/create', [AdminPageController::class, 'pegawaiCreate'])->name('pegawai.create');
    Route::get('/pegawai/{id}/edit', [AdminPageController::class, 'pegawaiEdit'])->name('pegawai.edit');
    Route::get('/catatan', [AdminPageController::class, 'catatan'])->name('catatan');
    Route::get('/catatan/{id}/edit', [AdminPageController::class, 'catatanEdit'])->name('catatan.edit');
    Route::get('/rekap', [AdminPageController::class, 'rekap'])->name('rekap');
    Route::get('/pengaturan', [AdminPageController::class, 'pengaturan'])->name('pengaturan');
});

// Pimpinan
Route::middleware(['auth', 'role:pimpinan'])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    Route::get('/rekap', [PimpinanPageController::class, 'rekap'])->name('rekap');
});
