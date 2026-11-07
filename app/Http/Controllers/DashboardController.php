<?php

namespace App\Http\Controllers;

use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show application dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        // Calculate counts based on role
        if ($user->canManageArchives()) {
            $pendingCount = DocumentLoan::pending()->count();
            $borrowedCount = DocumentLoan::borrowed()->count();
        } else {
            $pendingCount = DocumentLoan::where('borrower_id', $user->id)->pending()->count();
            $borrowedCount = DocumentLoan::where('borrower_id', $user->id)->borrowed()->count();
        }

        $totalDocuments = ArchiveDocument::count();

        return view('dashboard.index', compact('user', 'pendingCount', 'borrowedCount', 'totalDocuments'));
    }
}
