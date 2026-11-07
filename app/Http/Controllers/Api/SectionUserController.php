<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionUserController extends Controller
{
    /**
     * Mengambil daftar pegawai aktif di bawah suatu seksi (model binding).
     * Sesuai aturan A5 butir 12.
     */
    public function users(Section $section): JsonResponse
    {
        $users = $section->users()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'section_id', 'name', 'username', 'nip']);

        return response()->json($users);
    }
}
