<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Get active banners
     */
    public function index(Request $request)
    {
        $banners = Banner::active()
            ->orderBy('position')
            ->get()
            ->map(function ($banner) {
                return [
                    'id'        => $banner->id,
                    'title'     => $banner->title,
                    'subtitle'  => $banner->subtitle,
                    'image_url' => $banner->image_url, // ⬅️ FINAL FIX
                    'link'      => null,
                    'position'  => $banner->position,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Banner loaded successfully',
            'data'    => $banners
        ]);
    }

    /**
     * Show single banner detail
     */
    public function show($id)
    {
        $banner = Banner::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'        => $banner->id,
                'title'     => $banner->title,
                'subtitle'  => $banner->subtitle,
                'image_url' => $banner->image_url,
                'link'      => null,
                'position'  => $banner->position,
            ]
        ]);
    }
}
