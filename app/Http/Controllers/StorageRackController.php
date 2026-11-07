<?php

namespace App\Http\Controllers;

use App\Actions\StorageRack\CreateStorageRack;
use App\Actions\StorageRack\DeleteStorageRack;
use App\Actions\StorageRack\UpdateStorageRack;
use App\Http\Requests\StorageRack\StoreStorageRackRequest;
use App\Http\Requests\StorageRack\UpdateStorageRackRequest;
use App\Models\StorageRack;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorageRackController extends Controller
{
    public function index(): View
    {
        return view('master.storage-racks.index');
    }

    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'archive_documents_count', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = StorageRack::withCount('archiveDocuments');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (StorageRack $rack) => [
                'id' => $rack->id,
                'name' => $rack->name,
                'documents_count' => $rack->archive_documents_count,
                'can_delete' => $rack->archive_documents_count === 0,
                'created_at' => $rack->created_at?->format('d/m/Y H:i'),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreStorageRackRequest $request, CreateStorageRack $action): JsonResponse|RedirectResponse
    {
        $rack = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rak penyimpanan berhasil ditambahkan.',
                'data' => $rack,
            ], 201);
        }

        return redirect()->route('master.storage-racks.index')->with('success', 'Rak penyimpanan berhasil ditambahkan.');
    }

    public function update(UpdateStorageRackRequest $request, StorageRack $storageRack, UpdateStorageRack $action): JsonResponse|RedirectResponse
    {
        $action->execute($storageRack, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rak penyimpanan berhasil diperbarui.',
                'data' => $storageRack,
            ]);
        }

        return redirect()->route('master.storage-racks.index')->with('success', 'Rak penyimpanan berhasil diperbarui.');
    }

    public function destroy(Request $request, StorageRack $storageRack, DeleteStorageRack $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($storageRack);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('master.storage-racks.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Rak penyimpanan berhasil dihapus.',
            ]);
        }

        return redirect()->route('master.storage-racks.index')->with('success', 'Rak penyimpanan berhasil dihapus.');
    }
}
