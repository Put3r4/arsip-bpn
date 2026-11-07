<?php

namespace App\Http\Controllers;

use App\Actions\LandRightType\CreateLandRightType;
use App\Actions\LandRightType\DeleteLandRightType;
use App\Actions\LandRightType\UpdateLandRightType;
use App\Http\Requests\LandRightType\StoreLandRightTypeRequest;
use App\Http\Requests\LandRightType\UpdateLandRightTypeRequest;
use App\Models\LandRightType;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandRightTypeController extends Controller
{
    public function index(): View
    {
        return view('master.land-right-types.index');
    }

    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'archive_documents_count', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = LandRightType::withCount('archiveDocuments');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (LandRightType $type) => [
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

    public function store(StoreLandRightTypeRequest $request, CreateLandRightType $action): JsonResponse|RedirectResponse
    {
        $type = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis hak tanah berhasil ditambahkan.',
                'data' => $type,
            ], 201);
        }

        return redirect()->route('master.land-right-types.index')->with('success', 'Jenis hak tanah berhasil ditambahkan.');
    }

    public function update(UpdateLandRightTypeRequest $request, LandRightType $landRightType, UpdateLandRightType $action): JsonResponse|RedirectResponse
    {
        $action->execute($landRightType, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis hak tanah berhasil diperbarui.',
                'data' => $landRightType,
            ]);
        }

        return redirect()->route('master.land-right-types.index')->with('success', 'Jenis hak tanah berhasil diperbarui.');
    }

    public function destroy(Request $request, LandRightType $landRightType, DeleteLandRightType $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($landRightType);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('master.land-right-types.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Jenis hak tanah berhasil dihapus.',
            ]);
        }

        return redirect()->route('master.land-right-types.index')->with('success', 'Jenis hak tanah berhasil dihapus.');
    }
}
