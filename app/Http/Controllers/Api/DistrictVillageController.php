<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\JsonResponse;

class DistrictVillageController extends Controller
{
    /**
     * Mengambil daftar desa berdasarkan kecamatan (model binding char 7).
     * Sesuai aturan A5 butir 12, WIL-01, dan WIL-02.
     */
    public function villages(District $district): JsonResponse
    {
        $villages = $district->villages()
            ->orderBy('name', 'asc')
            ->get(['id', 'district_id', 'name']);

        return response()->json($villages);
    }
}
