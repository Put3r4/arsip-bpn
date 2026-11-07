<?php

use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    $pendingCount = DocumentLoan::pending()->count();
    $borrowedCount = DocumentLoan::borrowed()->count();
    $totalDocuments = ArchiveDocument::count();

    return view('dashboard.index', compact('pendingCount', 'borrowedCount', 'totalDocuments'));
})->name('dashboard');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::patch('/profile/theme', function (Request $request) {
    $validated = $request->validate([
        'theme' => 'required|string|in:samudra,marun,senja',
    ]);

    if (auth()->check()) {
        auth()->user()->update(['theme' => $validated['theme']]);
    }

    return response()->json([
        'status' => 'success',
        'theme' => $validated['theme'],
    ]);
})->name('profile.theme');
