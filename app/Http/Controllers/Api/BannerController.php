<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * GET /api/banners
     *
     * Menampilkan semua banner yang sedang aktif (untuk homepage/slider)
     */
    public function index(Request $request)
    {
        $banners = Banner::active()
            ->orderBy('position')
            ->get()
            ->makeHidden(['created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at']);

        return response()->json([
            'success' => true,
            'message' => 'Banner berhasil dimuat',
            'data'    => $banners
        ]);
    }

    /**
     * GET /api/banners/{id}
     *
     * Opsional: kalau frontend butuh detail banner tertentu
     */
    public function show($id)
    {
        $banner = Banner::active()->find($id);

        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner tidak ditemukan atau tidak aktif'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $banner->makeHidden(['created_by', 'updated_by', 'created_at', 'updated_at'])
        ]);
    }
}
