<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PeminjamanSiswaController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:20,1')->group(function () {
    Route::get('/pinjam', [PeminjamanSiswaController::class, 'index'])->name('siswa.pinjam');
    Route::get('/pinjam/cari-barang', [PeminjamanSiswaController::class, 'cariBarang'])->name('siswa.cari-barang');
    Route::post('/pinjam/ajukan', [PeminjamanSiswaController::class, 'ajukan'])->name('siswa.ajukan');
    Route::post('/pinjam/cek-kode', [PeminjamanSiswaController::class, 'cekKode'])->name('siswa.cek-kode');
    Route::post('/pinjam/kembalikan', [PeminjamanSiswaController::class, 'kembalikan'])->name('siswa.kembalikan');
});

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Barang
    Route::get('/barang', [BarangController::class, 'index'])->name('barang.index');
    Route::get('/barang/tambah', [BarangController::class, 'create'])->name('barang.create');
    Route::post('/barang', [BarangController::class, 'store'])->name('barang.store');
    Route::get('/barang/{barang}/edit', [BarangController::class, 'edit'])->name('barang.edit');
    Route::get('/barang/{barang}/unit', [BarangController::class, 'unitList'])->name('barang.unit');
    Route::patch('/barang/{barang}/unit/{unit}', [BarangController::class, 'updateUnit'])->name('barang.unit.update');
    Route::patch('/barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
    Route::delete('/barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
    Route::get('/barang/{barang}', [BarangController::class, 'show'])->name('barang.show');

    // Kategori
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::patch('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    // Transaksi
    Route::get('/transaksi/masuk', [TransaksiController::class, 'masuk'])->name('transaksi.masuk');
    Route::post('/transaksi/masuk', [TransaksiController::class, 'simpanMasuk'])->name('transaksi.simpan-masuk');
    Route::get('/transaksi/keluar', [TransaksiController::class, 'keluar'])->name('transaksi.keluar');
    Route::post('/transaksi/keluar', [TransaksiController::class, 'simpanKeluar'])->name('transaksi.simpan-keluar');

    // Peminjaman Admin
    Route::get('/peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::patch('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');

    // Pengguna
    Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
    Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
    Route::patch('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
    Route::delete('/pengguna/{pengguna}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');
    Route::patch('/pengguna/{pengguna}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.reset-password');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // API JSON
    Route::get('/api/barang/search', [BarangController::class, 'search'])->name('api.barang.search');
    Route::get('/api/unit/tersedia/{barang}', [BarangController::class, 'unitTersedia'])->name('api.unit.tersedia');
});

require __DIR__ . '/auth.php';
