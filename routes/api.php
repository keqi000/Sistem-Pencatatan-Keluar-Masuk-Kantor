<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QRController;
use App\Http\Controllers\Api\PindaianController;
use App\Http\Controllers\Api\IzinDinasController;
use App\Http\Controllers\Api\RiwayatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PegawaiController;
use App\Http\Controllers\Api\RekapController;
use App\Http\Controllers\Api\PengaturanController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes - SIKMA BPMP Gorontalo
|--------------------------------------------------------------------------
*/

// 1. Publik / Autentikasi
Route::post('/auth/login', [AuthController::class, 'login']);

// 2. Layar Publik / Hardware Polling (Layar Lobby & Layar Pos Security)
Route::get('/qr/current', [QRController::class, 'getCurrent']);
Route::post('/qr/validate', [QRController::class, 'validateToken']);
Route::post('/qr/refresh', [QRController::class, 'forceRefresh']);
Route::get('/pindaian/pos-hari-ini', [PindaianController::class, 'getHariIniPos']);

// 3. Rute Terproteksi (Sanctum Token / Session)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/check-session', [AuthController::class, 'checkSession']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Pindaian (Pegawai & Pos)
    Route::post('/pindaian/catat-keluar', [PindaianController::class, 'catatKeluar']);
    Route::post('/pindaian/catat-masuk', [PindaianController::class, 'catatMasuk']);
    Route::get('/pindaian/status-saya', [PindaianController::class, 'getStatusSaya']);
    Route::get('/pindaian/semua-pegawai-status', [PindaianController::class, 'getAllPegawaiStatus'])->middleware('role:pos,admin');
    Route::post('/pindaian/catat-manual-pos', [PindaianController::class, 'catatManualPos'])->middleware('role:pos,admin');
    Route::post('/pindaian/tutup-manual', [PindaianController::class, 'tutupManual'])->middleware('role:admin');
    Route::put('/pindaian/{id}', [PindaianController::class, 'update'])->middleware('role:admin');
    Route::delete('/pindaian/{id}', [PindaianController::class, 'destroy'])->middleware('role:admin');

    // Izin Dinas
    Route::get('/izin-dinas', [IzinDinasController::class, 'getListSaya']);
    Route::post('/izin-dinas', [IzinDinasController::class, 'ajukan']);
    Route::get('/izin-dinas/bawahan', [IzinDinasController::class, 'getListBawahan'])->middleware('role:atasan,admin,pimpinan');
    Route::post('/izin-dinas/{id}/putuskan', [IzinDinasController::class, 'putuskan'])->middleware('role:atasan,admin,pimpinan');
    Route::get('/izin-dinas/aktif-hari-ini', [IzinDinasController::class, 'getAktifHariIni']);

    // Riwayat
    Route::get('/riwayat/saya', [RiwayatController::class, 'getSaya']);
    Route::get('/riwayat/all', [RiwayatController::class, 'getAll'])->middleware('role:admin,pimpinan,pos');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStatsHariIni'])->middleware('role:admin,pimpinan,atasan,pos');
    Route::get('/dashboard/sedang-diluar', [DashboardController::class, 'getSedangDiluar'])->middleware('role:admin,pimpinan,atasan,pos');
    Route::get('/dashboard/chart', [DashboardController::class, 'getChartData'])->middleware('role:admin,pimpinan,atasan');

    // Pegawai (CRUD)
    Route::get('/pegawai', [PegawaiController::class, 'index']);
    Route::get('/pegawai/{id}', [PegawaiController::class, 'show']);
    Route::post('/pegawai', [PegawaiController::class, 'store'])->middleware('role:admin');
    Route::post('/pegawai/{id}', [PegawaiController::class, 'update'])->middleware('role:admin');
    Route::delete('/pegawai/{id}', [PegawaiController::class, 'destroy'])->middleware('role:admin');

    // Rekap
    Route::get('/rekap/harian', [RekapController::class, 'harian'])->middleware('role:admin,pimpinan');
    Route::get('/rekap/bulanan', [RekapController::class, 'bulanan'])->middleware('role:admin,pimpinan');
    Route::get('/rekap/export-pdf', [RekapController::class, 'exportPdf'])->middleware('role:admin,pimpinan');
    Route::get('/rekap/export-excel', [RekapController::class, 'exportExcel'])->middleware('role:admin,pimpinan');

    // Pengaturan
    Route::get('/pengaturan/jam-kerja', [PengaturanController::class, 'getJamKerja'])->middleware('role:admin,pimpinan');
    Route::post('/pengaturan/jam-kerja', [PengaturanController::class, 'updateJamKerja'])->middleware('role:admin');
    Route::get('/pengaturan/unit-kerja', [PengaturanController::class, 'getUnitKerja']);
    Route::post('/pengaturan/unit-kerja', [PengaturanController::class, 'crudUnitKerja'])->middleware('role:admin');

    // User (CRUD)
    Route::get('/users', [UserController::class, 'index'])->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->middleware('role:admin');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('role:admin');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('role:admin');
    Route::post('/users/reset-password', [UserController::class, 'resetPassword'])->middleware('role:admin');
});
