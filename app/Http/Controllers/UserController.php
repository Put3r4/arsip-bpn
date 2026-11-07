<?php

namespace App\Http\Controllers;

use App\Actions\User\CreateUser;
use App\Actions\User\DeleteUser;
use App\Actions\User\ResetUserPassword;
use App\Actions\User\UpdateUser;
use App\Enums\UserRole;
use App\Http\Requests\User\ResetUserPasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Section;
use App\Models\User;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $sections = Section::orderBy('name')->get();
        $roles = UserRole::cases();

        return view('users.index', compact('sections', 'roles'));
    }

    /**
     * JSON dataset for x-data-table server-side processing.
     */
    public function data(Request $request): JsonResponse
    {
        $allowedSorts = ['id', 'name', 'username', 'nip', 'role', 'is_active', 'created_at'];

        $sort = in_array($request->query('sort'), $allowedSorts, true) ? $request->query('sort') : 'id';
        $direction = strtolower($request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        $query = User::with('section')
            ->withCount(['loans', 'approvedLoans', 'receivedLoans']);

        // Search across name, username, and nip
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->query('role')) {
            if (UserRole::tryFrom($role)) {
                $query->where('role', $role);
            }
        }

        // Filter by active status
        if ($request->has('is_active') && $request->query('is_active') !== '' && $request->query('is_active') !== null) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by section
        if ($sectionId = $request->query('section_id')) {
            $query->where('section_id', $sectionId);
        }

        $paginator = $query->orderBy($sort, $direction)->paginate($perPage);

        $currentUserId = $request->user()->id;

        return response()->json([
            'data' => collect($paginator->items())->map(function (User $user) use ($currentUserId) {
                $totalLoans = $user->loans_count + $user->approved_loans_count + $user->received_loans_count;
                $isSelf = $user->id === $currentUserId;
                $canDelete = ! $isSelf && $totalLoans === 0;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'nip' => $user->nip ?? '-',
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'is_active' => (bool) $user->is_active,
                    'section_id' => $user->section_id,
                    'section_name' => $user->section?->name ?? '-',
                    'theme' => $user->theme,
                    'is_self' => $isSelf,
                    'total_loans' => $totalLoans,
                    'can_delete' => $canDelete,
                    'created_at' => $user->created_at?->format('d/m/Y H:i'),
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
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request, CreateUser $action): JsonResponse|RedirectResponse
    {
        $user = $action->execute($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna berhasil ditambahkan.',
                'data' => $user,
            ], 201);
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): JsonResponse|RedirectResponse
    {
        $action->execute($user, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data pengguna berhasil diperbarui.',
                'data' => $user,
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Reset password of the specified user.
     */
    public function resetPassword(ResetUserPasswordRequest $request, User $user, ResetUserPassword $action): JsonResponse|RedirectResponse
    {
        $action->execute($user, $request->validated()['password']);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kata sandi pengguna berhasil direset.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Kata sandi pengguna berhasil direset.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user, DeleteUser $action): JsonResponse|RedirectResponse
    {
        try {
            $action->execute($request->user(), $user);
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('users.index')->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna berhasil dihapus.',
            ]);
        }

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
