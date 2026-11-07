<?php

namespace App\Http\Controllers;

use App\Actions\ArchiveDocument\CreateArchiveDocument;
use App\Actions\ArchiveDocument\DeleteArchiveDocument;
use App\Actions\ArchiveDocument\UpdateArchiveDocument;
use App\Actions\ArchiveDocument\UpdateDocumentPhysicalStatus;
use App\Http\Requests\ArchiveDocument\StoreArchiveDocumentRequest;
use App\Http\Requests\ArchiveDocument\UpdateArchiveDocumentRequest;
use App\Http\Requests\ArchiveDocument\UpdateDocumentStatusRequest;
use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\StorageRack;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveDocumentController extends Controller
{
    /**
     * Tampilan utama Katalog Berkas.
     */
    public function index(): View
    {
        $districts = District::orderBy('name')->get(['id', 'name']);
        $documentTypes = DocumentType::orderBy('name')->get(['id', 'name']);
        $landRightTypes = LandRightType::orderBy('name')->get(['id', 'name']);
        $storageRacks = StorageRack::orderBy('name')->get(['id', 'name']);
        $sections = Section::orderBy('name')->get(['id', 'name']);
        $serviceActivities = ServiceActivity::orderBy('name')->get(['id', 'name']);

        return view('documents.index', compact(
            'districts',
            'documentTypes',
            'landRightTypes',
            'storageRacks',
            'sections',
            'serviceActivities'
        ));
    }

    /**
     * Data JSON Katalog Berkas untuk server-side processing pada x-data-table.
     */
    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'document_number', 'year', 'range_number', 'status', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = ArchiveDocument::with([
            'district',
            'village',
            'documentType',
            'landRightType',
            'storageRack',
            'activeLoan',
        ]);

        // Filter: Pencarian umum teks
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                    ->orWhere('range_number', 'like', "%{$search}%");
            });
        }

        // Filter: Nomor Berkas parsial spesifik
        if ($docNum = trim((string) $request->query('document_number'))) {
            $query->where('document_number', 'like', "%{$docNum}%");
        }

        // Filter: Kecamatan
        if ($districtId = $request->query('district_id')) {
            $query->where('district_id', $districtId);
        }

        // Filter: Desa
        if ($villageId = $request->query('village_id')) {
            $query->where('village_id', $villageId);
        }

        // Filter: Jenis Hak
        if ($landRightTypeId = $request->query('land_right_type_id')) {
            $query->where('land_right_type_id', $landRightTypeId);
        }

        // Filter: Jenis Berkas
        if ($documentTypeId = $request->query('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        // Filter: Tahun
        if ($year = $request->query('year')) {
            $query->where('year', $year);
        }

        // Filter: Status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);
        $user = $request->user();

        return response()->json([
            'data' => collect($paginator->items())->map(function (ArchiveDocument $doc) use ($user) {
                $hasActiveLoan = $doc->hasActiveLoan();
                $canManage = $user?->canManageArchives() ?? false;

                return [
                    'id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'year' => $doc->year ?? '-',
                    'raw_year' => $doc->year,
                    'storage_rack' => [
                        'id' => $doc->storage_rack_id,
                        'name' => $doc->storageRack?->name ?? '-',
                    ],
                    'document_type' => [
                        'id' => $doc->document_type_id,
                        'name' => $doc->documentType?->name ?? '-',
                    ],
                    'land_right_type' => [
                        'id' => $doc->land_right_type_id,
                        'name' => $doc->landRightType?->name ?? '-',
                    ],
                    'range_number' => $doc->range_number ?? '-',
                    'raw_range_number' => $doc->range_number,
                    'district' => [
                        'id' => $doc->district_id,
                        'name' => $doc->district?->name ?? '-',
                    ],
                    'village' => [
                        'id' => $doc->village_id,
                        'name' => $doc->village?->name ?? '-',
                    ],
                    'status' => $doc->status->value,
                    'status_label' => $doc->status->label(),
                    'status_badge' => $doc->status->badgeClasses(),
                    'has_file' => ! empty($doc->file_path),
                    'original_filename' => $doc->original_filename,
                    'file_size' => $doc->file_size,
                    'mime_type' => $doc->mime_type,
                    'created_at' => $doc->created_at?->format('d/m/Y H:i'),
                    'can' => [
                        'preview' => ! empty($doc->file_path),
                        'edit' => $canManage,
                        'update_status' => $canManage && ! $hasActiveLoan,
                        'delete' => $canManage && ! $hasActiveLoan,
                        'book' => $doc->status === \App\Enums\DocumentStatus::AVAILABLE,
                        'view_history' => $canManage,
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
     * Menyimpan berkas baru.
     */
    public function store(StoreArchiveDocumentRequest $request, CreateArchiveDocument $action): JsonResponse|RedirectResponse
    {
        $document = $action->execute($request->validated(), $request->file('file'));

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Berkas arsip berhasil ditambahkan.',
                'data' => $document,
            ], 201);
        }

        return redirect()->route('documents.index')->with('success', 'Berkas arsip berhasil ditambahkan.');
    }

    /**
     * Memperbarui data berkas arsip.
     */
    public function update(UpdateArchiveDocumentRequest $request, ArchiveDocument $document, UpdateArchiveDocument $action): JsonResponse|RedirectResponse
    {
        $action->execute($document, $request->validated(), $request->file('file'));

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Berkas arsip berhasil diperbarui.',
                'data' => $document,
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Berkas arsip berhasil diperbarui.');
    }

    /**
     * Memperbarui status fisik dokumen (available, lost, invalid).
     */
    public function updateStatus(UpdateDocumentStatusRequest $request, ArchiveDocument $document, UpdateDocumentPhysicalStatus $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($document, $request->validated('status'));
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('documents.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status fisik dokumen berhasil diubah.',
                'data' => $document,
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Status fisik dokumen berhasil diubah.');
    }

    /**
     * Menghapus dokumen (soft delete).
     */
    public function destroy(Request $request, ArchiveDocument $document, DeleteArchiveDocument $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($document);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('documents.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Berkas arsip berhasil dihapus.',
            ]);
        }

        return redirect()->route('documents.index')->with('success', 'Berkas arsip berhasil dihapus.');
    }

    /**
     * Pratinjau file pindaian dokumen (inline).
     * Sesuai A6: Storage::response() dengan Content-Disposition: inline, file hilang menghasilkan 404 yang rapi.
     */
    public function file(Request $request, ArchiveDocument $document): StreamedResponse
    {
        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'File scan dokumen tidak ditemukan di penyimpanan server.');
        }

        $headers = [
            'Content-Disposition' => 'inline; filename="' . ($document->original_filename ?: basename($document->file_path)) . '"',
        ];

        if ($document->mime_type) {
            $headers['Content-Type'] = $document->mime_type;
        }

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_filename ?: basename($document->file_path),
            $headers
        );
    }
}
