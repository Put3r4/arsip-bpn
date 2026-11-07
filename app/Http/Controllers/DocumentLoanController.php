<?php

namespace App\Http\Controllers;

use App\Actions\DocumentLoan\ApproveLoan;
use App\Actions\DocumentLoan\BookDocument;
use App\Actions\DocumentLoan\CancelLoan;
use App\Actions\DocumentLoan\ReturnLoan;
use App\Enums\LoanStatus;
use App\Http\Requests\DocumentLoan\BookDocumentRequest;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\ServiceActivity;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentLoanController extends Controller
{
    /**
     * Tampilan utama halaman Peminjaman Berkas (Tab Aktif & Riwayat Pengembalian).
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'active');
        $status = $request->query('status', '');
        $serviceActivities = ServiceActivity::orderBy('name')->get(['id', 'name']);

        return view('loans.index', compact('tab', 'status', 'serviceActivities'));
    }

    /**
     * Data JSON Peminjaman untuk server-side processing pada x-data-table.
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab = $request->query('tab', 'active');
        $allowedSorts = ['id', 'loan_date', 'return_date', 'status', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = DocumentLoan::with([
            'archiveDocument.storageRack',
            'borrower.section',
            'serviceActivity',
            'approver',
            'receiver',
        ]);

        // Filter Role: Staf melihat seluruh data kantor, Pegawai hanya miliknya (A5 butir 8)
        if (! $user->canManageArchives()) {
            $query->where('borrower_id', $user->id);
        }

        // Filter Tab: Aktif (pending, borrowed) vs Riwayat (returned, cancelled)
        if ($tab === 'active') {
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            } else {
                $query->whereIn('status', [LoanStatus::PENDING, LoanStatus::BORROWED]);
            }
        } elseif ($tab === 'returned') {
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            } else {
                $query->whereIn('status', [LoanStatus::RETURNED, LoanStatus::CANCELLED]);
            }
        } elseif ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter Pencarian Teks
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('archiveDocument', function ($docQ) use ($search) {
                    $docQ->where('document_number', 'like', "%{$search}%");
                })
                ->orWhereHas('borrower', function ($userQ) use ($search) {
                    $userQ->where('name', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                })
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filter Kegiatan Pelayanan
        if ($activityId = $request->query('service_activity_id')) {
            $query->where('service_activity_id', $activityId);
        }

        // Filter Pegawai Peminjam (hanya jika user adalah staf pengelola arsip)
        if ($user->canManageArchives() && $borrowerId = $request->query('borrower_id')) {
            $query->where('borrower_id', $borrowerId);
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(function (DocumentLoan $loan) use ($user) {
                return [
                    'id' => $loan->id,
                    'document' => [
                        'id' => $loan->archiveDocument?->id,
                        'document_number' => $loan->archiveDocument?->document_number ?? '-',
                        'year' => $loan->archiveDocument?->year ?? '-',
                        'range_number' => $loan->archiveDocument?->range_number ?? '-',
                        'storage_rack' => $loan->archiveDocument?->storageRack?->name ?? '-',
                        'has_file' => ! empty($loan->archiveDocument?->file_path),
                    ],
                    'borrower' => [
                        'id' => $loan->borrower?->id,
                        'name' => $loan->borrower?->name ?? '-',
                        'nip' => $loan->borrower?->nip ?? '-',
                        'section' => $loan->borrower?->section?->name ?? '-',
                    ],
                    'service_activity' => [
                        'id' => $loan->service_activity_id,
                        'name' => $loan->serviceActivity?->name ?? '-',
                    ],
                    'approver' => [
                        'id' => $loan->approver?->id,
                        'name' => $loan->approver?->name ?? '-',
                    ],
                    'receiver' => [
                        'id' => $loan->receiver?->id,
                        'name' => $loan->receiver?->name ?? '-',
                    ],
                    'loan_date' => $loan->loan_date?->format('d/m/Y') ?? '-',
                    'return_date' => $loan->return_date?->format('d/m/Y') ?? '-',
                    'raw_loan_date' => $loan->loan_date?->toDateString(),
                    'raw_return_date' => $loan->return_date?->toDateString(),
                    'approved_at' => $loan->approved_at?->format('d/m/Y H:i') ?? '-',
                    'cancelled_at' => $loan->cancelled_at?->format('d/m/Y H:i') ?? '-',
                    'status' => $loan->status->value,
                    'status_label' => $loan->status->label(),
                    'status_badge' => $loan->status->badgeClasses(),
                    'notes' => $loan->notes ?? '-',
                    'created_at' => $loan->created_at?->format('d/m/Y H:i'),
                    'can' => [
                        'approve' => $user->can('approve', $loan) && $loan->isPending(),
                        'return' => $user->can('return', $loan) && $loan->isBorrowed(),
                        'cancel' => $user->can('cancel', $loan) && $loan->isPending(),
                    ],
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Menyimpan pengajuan permohonan booking peminjaman berkas.
     */
    public function store(BookDocumentRequest $request, BookDocument $action): JsonResponse|RedirectResponse
    {
        try {
            $loan = $action->execute($request->validated());
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan peminjaman berkas berhasil diajukan.',
                'data' => $loan,
            ], 201);
        }

        return redirect()->route('loans.index')->with('success', 'Permohonan peminjaman berkas berhasil diajukan.');
    }

    /**
     * Menyetujui peminjaman berkas (ACC) dan serah terima fisik (Staf Arsip).
     */
    public function approve(Request $request, DocumentLoan $loan, ApproveLoan $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('approve', $loan);

        try {
            $updatedLoan = $action->execute($loan, $request->user());
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Peminjaman berkas berhasil disetujui.',
                'data' => $updatedLoan,
            ]);
        }

        return redirect()->back()->with('success', 'Peminjaman berkas berhasil disetujui.');
    }

    /**
     * Memproses pengembalian berkas fisik ke rak arsip (Staf Arsip).
     */
    public function returnLoan(Request $request, DocumentLoan $loan, ReturnLoan $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('return', $loan);

        try {
            $updatedLoan = $action->execute($loan, $request->user());
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengembalian berkas fisik berhasil diproses.',
                'data' => $updatedLoan,
            ]);
        }

        return redirect()->back()->with('success', 'Pengembalian berkas fisik berhasil diproses.');
    }

    /**
     * Membatalkan permohonan booking peminjaman.
     */
    public function cancel(Request $request, DocumentLoan $loan, CancelLoan $action): JsonResponse|RedirectResponse
    {
        Gate::authorize('cancel', $loan);

        try {
            $updatedLoan = $action->execute($loan);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan peminjaman berkas berhasil dibatalkan.',
                'data' => $updatedLoan,
            ]);
        }

        return redirect()->back()->with('success', 'Permohonan peminjaman berkas berhasil dibatalkan.');
    }

    /**
     * Menampilkan riwayat jejak audit peminjaman untuk dokumen spesifik (Staf Arsip).
     * Endpoint: GET /documents/{document}/history
     */
    public function history(Request $request, ArchiveDocument $document): JsonResponse|View
    {
        Gate::authorize('viewDocumentHistory', DocumentLoan::class);

        $loans = $document->loans()
            ->with(['borrower.section', 'serviceActivity', 'approver', 'receiver'])
            ->orderBy('id', 'desc')
            ->get();

        $historyData = $loans->map(function (DocumentLoan $loan) {
            return [
                'id' => $loan->id,
                'loan_date' => $loan->loan_date?->format('d/m/Y') ?? '-',
                'return_date' => $loan->return_date?->format('d/m/Y') ?? '-',
                'status' => $loan->status->value,
                'status_label' => $loan->status->label(),
                'status_badge' => $loan->status->badgeClasses(),
                'borrower' => [
                    'name' => $loan->borrower?->name ?? '-',
                    'section' => $loan->borrower?->section?->name ?? '-',
                ],
                'service_activity' => $loan->serviceActivity?->name ?? '-',
                'approver' => $loan->approver?->name ?? '-',
                'receiver' => $loan->receiver?->name ?? '-',
                'notes' => $loan->notes ?? '-',
                'approved_at' => $loan->approved_at?->format('d/m/Y H:i') ?? '-',
                'cancelled_at' => $loan->cancelled_at?->format('d/m/Y H:i') ?? '-',
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'document' => [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'year' => $document->year ?? '-',
                ],
                'history' => $historyData,
            ]);
        }

        return view('loans.history', [
            'document' => $document,
            'loans' => $loans,
            'historyData' => $historyData,
        ]);
    }

    /**
     * Rute bantu / alias untuk AUDIT-01: /loans/returned
     */
    public function returnedIndex(Request $request): View
    {
        $tab = 'returned';
        $status = $request->query('status', '');
        $serviceActivities = ServiceActivity::orderBy('name')->get(['id', 'name']);

        return view('loans.index', compact('tab', 'status', 'serviceActivities'));
    }

    /**
     * Rute bantu / alias untuk AUDIT-02: /my-loans
     */
    public function myLoans(Request $request): View
    {
        $tab = $request->query('tab', 'active');
        $status = $request->query('status', '');
        $serviceActivities = ServiceActivity::orderBy('name')->get(['id', 'name']);

        return view('loans.index', compact('tab', 'status', 'serviceActivities'));
    }
}
