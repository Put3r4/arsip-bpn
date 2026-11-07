<?php

namespace App\Http\Controllers;

use App\Actions\DocumentType\CreateDocumentType;
use App\Actions\DocumentType\DeleteDocumentType;
use App\Actions\DocumentType\UpdateDocumentType;
use App\Http\Requests\DocumentType\StoreDocumentTypeRequest;
use App\Http\Requests\DocumentType\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        return view('master.document-types.index');
    }

    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'archive_documents_count', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = DocumentType::withCount('archiveDocuments');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (DocumentType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'documents_count' => $type->archive_documents_count,
                'can_delete' => $type->archive_documents_count === 0,
                'created_at' => $type->created_at?->format('d/m/Y H:i'),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreDocumentTypeRequest $request, CreateDocumentType $action): JsonResponse|RedirectResponse
    {
        $type = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis berkas berhasil ditambahkan.',
                'data' => $type,
            ], 201);
        }

        return redirect()->route('master.document-types.index')->with('success', 'Jenis berkas berhasil ditambahkan.');
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType, UpdateDocumentType $action): JsonResponse|RedirectResponse
    {
        $action->execute($documentType, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis berkas berhasil diperbarui.',
                'data' => $documentType,
            ]);
        }

        return redirect()->route('master.document-types.index')->with('success', 'Jenis berkas berhasil diperbarui.');
    }

    public function destroy(Request $request, DocumentType $documentType, DeleteDocumentType $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($documentType);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('master.document-types.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis berkas berhasil dihapus.',
            ]);
        }

        return redirect()->route('master.document-types.index')->with('success', 'Jenis berkas berhasil dihapus.');
    }
}
