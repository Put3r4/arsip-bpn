<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateThemeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show profile page.
     */
    public function edit(Request $request): View
    {
        return view('profile.index', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->validated()['password']),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kata sandi berhasil diperbarui.',
            ]);
        }

        return redirect()->route('profile.edit')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    /**
     * Update user theme preference (Real endpoint for PATCH /profile/theme).
     */
    public function updateTheme(UpdateThemeRequest $request): JsonResponse
    {
        $theme = $request->validated()['theme'];

        $request->user()->update([
            'theme' => $theme,
        ]);

        return response()->json([
            'status' => 'success',
            'theme' => $theme,
            'message' => 'Tema tampilan berhasil disimpan.',
        ]);
    }
}
