<?php

namespace App\Http\Controllers;

use App\Actions\Section\CreateSection;
use App\Actions\Section\DeleteSection;
use App\Actions\Section\UpdateSection;
use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Requests\Section\UpdateSectionRequest;
use App\Models\Section;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index(): View
    {
        return view('sections.index');
    }

    /**
     * Provide JSON dataset for x-data-table server-side processing.
     */
    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'users_count', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = Section::withCount('users');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'users_count' => $section->users_count,
                'can_delete' => $section->users_count === 0,
                'created_at' => $section->created_at?->format('d/m/Y H:i'),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreSectionRequest $request, CreateSection $action): JsonResponse|RedirectResponse
    {
        $section = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Seksi berhasil ditambahkan.',
                'data' => $section,
            ], 201);
        }

        return redirect()->route('master.sections.index')->with('success', 'Seksi berhasil ditambahkan.');
    }

    /**
     * Update the specified section.
     */
    public function update(UpdateSectionRequest $request, Section $section, UpdateSection $action): JsonResponse|RedirectResponse
    {
        $action->execute($section, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Seksi berhasil diperbarui.',
                'data' => $section,
            ]);
        }

        return redirect()->route('master.sections.index')->with('success', 'Seksi berhasil diperbarui.');
    }

    /**
     * Remove the specified section from storage.
     */
    public function destroy(Request $request, Section $section, DeleteSection $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($section);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('master.sections.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Seksi berhasil dihapus.',
            ]);
        }

        return redirect()->route('master.sections.index')->with('success', 'Seksi berhasil dihapus.');
    }
}
