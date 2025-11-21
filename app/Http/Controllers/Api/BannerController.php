<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'active'); // default active
        $q = Banner::query();

        if ($status === 'active') {
            $q->active();
        } elseif (in_array($status, ['draft','inactive'])) {
            $q->where('status', $status);
        } // 'all' → tanpa filter status

        $banners = $q->orderBy('position')
            ->get(['id','title','subtitle','image_path','position']);

        $data = $banners->map(fn ($b) => [
            'id'        => $b->id,
            'title'     => $b->title,
            'subtitle'  => $b->subtitle,
            'image_url' => $b->image_url, // accessor
            'link'      => null,
            'position'  => $b->position,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Banner loaded successfully',
            'data'    => $data,
        ]);
}

    public function show($id)
    {
        $b = Banner::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => [
                'id'        => $b->id,
                'title'     => $b->title,
                'subtitle'  => $b->subtitle,
                'image_url' => $b->image_url,
                'link'      => null,
                'position'  => $b->position,
            ],
        ]);
    }
}
