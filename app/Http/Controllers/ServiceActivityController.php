<?php

namespace App\Http\Controllers;

use App\Actions\ServiceActivity\CreateServiceActivity;
use App\Actions\ServiceActivity\DeleteServiceActivity;
use App\Actions\ServiceActivity\UpdateServiceActivity;
use App\Http\Requests\ServiceActivity\StoreServiceActivityRequest;
use App\Http\Requests\ServiceActivity\UpdateServiceActivityRequest;
use App\Models\ServiceActivity;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceActivityController extends Controller
{
    public function index(): View
    {
        return view('master.service-activities.index');
    }

    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'loans_count', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = ServiceActivity::withCount('loans');

        if ($search = trim((string) $request->query('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (ServiceActivity $activity) => [
                'id' => $activity->id,
                'name' => $activity->name,
                'loans_count' => $activity->loans_count,
                'can_delete' => $activity->loans_count === 0,
                'created_at' => $activity->created_at?->format('d/m/Y H:i'),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreServiceActivityRequest $request, CreateServiceActivity $action): JsonResponse|RedirectResponse
    {
        $activity = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kegiatan pelayanan berhasil ditambahkan.',
                'data' => $activity,
            ], 201);
        }

        return redirect()->route('master.service-activities.index')->with('success', 'Kegiatan pelayanan berhasil ditambahkan.');
    }

    public function update(UpdateServiceActivityRequest $request, ServiceActivity $serviceActivity, UpdateServiceActivity $action): JsonResponse|RedirectResponse
    {
        $action->execute($serviceActivity, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kegiatan pelayanan berhasil diperbarui.',
                'data' => $serviceActivity,
            ]);
        }

        return redirect()->route('master.service-activities.index')->with('success', 'Kegiatan pelayanan berhasil diperbarui.');
    }

    public function destroy(Request $request, ServiceActivity $serviceActivity, DeleteServiceActivity $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($serviceActivity);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('master.service-activities.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kegiatan pelayanan berhasil dihapus.',
            ]);
        }

        return redirect()->route('master.service-activities.index')->with('success', 'Kegiatan pelayanan berhasil dihapus.');
    }
}
