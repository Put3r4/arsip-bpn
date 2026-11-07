<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SectionController;
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

    // Administration (Super Admin Only)
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
