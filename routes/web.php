<?php

use App\Http\Controllers\Api\DistrictVillageController;
use App\Http\Controllers\Api\SectionUserController;
use App\Http\Controllers\ArchiveDocumentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentLoanController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\LandRightTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ServiceActivityController;
use App\Http\Controllers\StorageRackController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Informasi Arsip Kantah BPN Sumbawa
|--------------------------------------------------------------------------
*/

// Public / Root
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');

    // Katalog Berkas (Semua Peran Terotentikasi: Super Admin, Archivist, Employee)
    Route::get('/documents', [ArchiveDocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/data', [ArchiveDocumentController::class, 'data'])->name('documents.data');
    Route::get('/documents/{document}/file', [ArchiveDocumentController::class, 'file'])->name('documents.file');
    Route::get('/documents/{document}/preview', [ArchiveDocumentController::class, 'file'])->name('documents.preview');

    // Peminjaman Berkas (Semua Peran Terotentikasi: Super Admin, Archivist, Employee)
    Route::get('/loans', [DocumentLoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/data', [DocumentLoanController::class, 'data'])->name('loans.data');
    Route::post('/loans', [DocumentLoanController::class, 'store'])->name('loans.store');
    Route::post('/loans/{loan}/cancel', [DocumentLoanController::class, 'cancel'])->name('loans.cancel');
    Route::delete('/loans/{loan}', [DocumentLoanController::class, 'cancel'])->name('loans.destroy');
    Route::get('/loans/returned', [DocumentLoanController::class, 'returnedIndex'])->name('loans.returned');
    Route::get('/my-loans', [DocumentLoanController::class, 'myLoans'])->name('loans.my-loans');

    // Endpoint API Wilayah & Seksi (Dropdown Berantai - A5 butir 12)
    Route::get('/api/districts/{district}/villages', [DistrictVillageController::class, 'villages'])->name('api.districts.villages');
    Route::get('/api/sections/{section}/users', [SectionUserController::class, 'users'])->name('api.sections.users');

    // Pengelolaan Arsip & Master Data (Staf Arsip: Super Admin dan Archivist)
    Route::middleware('can:manage-archives')->group(function () {
        // Peminjaman Khusus Staf Arsip (Approve, Pengembalian, Riwayat Jejak Audit)
        Route::post('/loans/{loan}/approve', [DocumentLoanController::class, 'approve'])->name('loans.approve');
        Route::post('/loans/{loan}/return', [DocumentLoanController::class, 'returnLoan'])->name('loans.return');
        Route::get('/documents/{document}/history', [DocumentLoanController::class, 'history'])->name('documents.history');
        Route::get('/audit', [DocumentLoanController::class, 'returnedIndex'])->name('audit.index');

        // Berkas Arsip Mutasi
        Route::post('/documents', [ArchiveDocumentController::class, 'store'])->name('documents.store');
        Route::match(['put', 'patch'], '/documents/{document}', [ArchiveDocumentController::class, 'update'])->name('documents.update');
        Route::patch('/documents/{document}/status', [ArchiveDocumentController::class, 'updateStatus'])->name('documents.status');
        Route::delete('/documents/{document}', [ArchiveDocumentController::class, 'destroy'])->name('documents.destroy');

        // Master Rak Penyimpanan
        Route::get('/master/storage-racks', [StorageRackController::class, 'index'])->name('master.storage-racks.index');
        Route::get('/master/storage-racks/data', [StorageRackController::class, 'data'])->name('master.storage-racks.data');
        Route::post('/master/storage-racks', [StorageRackController::class, 'store'])->name('master.storage-racks.store');
        Route::match(['put', 'patch'], '/master/storage-racks/{storage_rack}', [StorageRackController::class, 'update'])->name('master.storage-racks.update');
        Route::delete('/master/storage-racks/{storage_rack}', [StorageRackController::class, 'destroy'])->name('master.storage-racks.destroy');

        // Master Jenis Berkas
        Route::get('/master/document-types', [DocumentTypeController::class, 'index'])->name('master.document-types.index');
        Route::get('/master/document-types/data', [DocumentTypeController::class, 'data'])->name('master.document-types.data');
        Route::post('/master/document-types', [DocumentTypeController::class, 'store'])->name('master.document-types.store');
        Route::match(['put', 'patch'], '/master/document-types/{document_type}', [DocumentTypeController::class, 'update'])->name('master.document-types.update');
        Route::delete('/master/document-types/{document_type}', [DocumentTypeController::class, 'destroy'])->name('master.document-types.destroy');

        // Master Jenis Hak Tanah
        Route::get('/master/land-right-types', [LandRightTypeController::class, 'index'])->name('master.land-right-types.index');
        Route::get('/master/land-right-types/data', [LandRightTypeController::class, 'data'])->name('master.land-right-types.data');
        Route::post('/master/land-right-types', [LandRightTypeController::class, 'store'])->name('master.land-right-types.store');
        Route::match(['put', 'patch'], '/master/land-right-types/{land_right_type}', [LandRightTypeController::class, 'update'])->name('master.land-right-types.update');
        Route::delete('/master/land-right-types/{land_right_type}', [LandRightTypeController::class, 'destroy'])->name('master.land-right-types.destroy');

        // Master Kegiatan Pelayanan
        Route::get('/master/service-activities', [ServiceActivityController::class, 'index'])->name('master.service-activities.index');
        Route::get('/master/service-activities/data', [ServiceActivityController::class, 'data'])->name('master.service-activities.data');
        Route::post('/master/service-activities', [ServiceActivityController::class, 'store'])->name('master.service-activities.store');
        Route::match(['put', 'patch'], '/master/service-activities/{service_activity}', [ServiceActivityController::class, 'update'])->name('master.service-activities.update');
        Route::delete('/master/service-activities/{service_activity}', [ServiceActivityController::class, 'destroy'])->name('master.service-activities.destroy');
    });

    // Administrasi Sistem (Khusus Super Admin)
    Route::middleware('can:super-admin')->group(function () {
        // Seksi Organisasi
        Route::get('/master/sections', [SectionController::class, 'index'])->name('master.sections.index');
        Route::get('/master/sections/data', [SectionController::class, 'data'])->name('master.sections.data');
        Route::post('/master/sections', [SectionController::class, 'store'])->name('master.sections.store');
        Route::match(['put', 'patch'], '/master/sections/{section}', [SectionController::class, 'update'])->name('master.sections.update');
        Route::delete('/master/sections/{section}', [SectionController::class, 'destroy'])->name('master.sections.destroy');

        // Manajemen Pengguna
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/data', [UserController::class, 'data'])->name('users.data');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
