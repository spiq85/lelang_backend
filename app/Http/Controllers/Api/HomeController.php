<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AuctionBatch;

class HomeController extends Controller
{
    public function index() 
    {
        $trending = Product::where('is_trending', true)
            ->where('status', 'published')
            ->with(['images','seller'])
            ->orderBy('trending_order', 'asc')
            ->limit(10)
            ->get();

            $closedAuction = AuctionBatch::where('status','closed')
            ->with(['products.coverImage','products.seller'])
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get();

            return response()->json([
                'trending' => $trending,
                'closed_auction' => $closedAuction,
            ]);
    }
}
