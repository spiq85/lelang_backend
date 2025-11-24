<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuctionBatch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * ============================
     * LIVE PRODUCTS (sedang dilelang)
     * ============================
     */
    public function live(Request $request)
    {
        $now = now();

        $query = Product::query()
            ->where('products.status','published')
            ->whereHas('batchLots.batch',function ($b) use ($now) {
                $b->where('auction_batches.status', 'published')
                    ->where('auction_batches.start_at', '<=', $now)
                    ->where('auction_batches.end_at', '>=', $now);
            })
            ->with([
                'images',
                'categories',
                'batches' => fn($q) => $q
                    ->select(
                        'auction_batches.id',
                        'auction_batches.title',
                        'auction_batches.start_at',
                        'auction_batches.end_at',
                        'auction_batches.status'
                    )
                    ->where('auction_batches.status', 'published'),
            ]);

        // Filter kategori
        if ($request->filled('category')) {
            $slug = $request->string('category');
            $query->whereHas('categories', fn($q) => $q->where('slug', $slug));
        }

        // Search
        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(function($w) use ($term){
                $w->where('product_name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $query->latest('products.created_at');

        return response()->json(
            $query->paginate($request->get('per_page', 12))
        );
    }

    /**
     * ============================
     * PRODUCT LISTING BARU (SELALU ADA)
     * ============================
     */
    public function listing(Request $request)
    {
        $now = now();

        $products = Product::query()
            ->where('products.status', 'published')
            ->with([
                'images',
                'categories',
                'batches' => fn($q) => $q->select(
                    'auction_batches.id',
                    'auction_batches.title',
                    'auction_batches.start_at',
                    'auction_batches.end_at',
                    'auction_batches.status'
                )
            ])
            ->latest()
            ->paginate($request->get('per_page', 12));

        // Map hasil agar FE gampang pake
        $mapped = $products->through(function ($p) use ($now) {

            $ongoing = $p->batches()
                ->where('auction_batches.status', 'published')
                ->where('auction_batches.start_at', '<=', $now)
                ->where('auction_batches.end_at', '>=', $now)
                ->first();

            $upcoming = $p->batches()
                ->where('auction_batches.status', 'published')
                ->where('auction_batches.start_at', '>', $now)
                ->orderBy('auction_batches.start_at')
                ->first();

            $ended = $p->batches()
                ->where('auction_batches.status', 'published')
                ->where('auction_batches.end_at', '<', $now)
                ->orderByDesc('auction_batches.end_at')
                ->first();

            return [
                'id' => $p->id,
                'product_name' => $p->product_name,
                'description' => $p->description,
                'base_price' => $p->base_price,
                'images' => $p->images,
                'categories' => $p->categories,

                'auction_status' =>
                    $ongoing ? 'ongoing' :
                    ($upcoming ? 'upcoming' :
                    ($ended ? 'ended' : 'no_auction')),

                'ongoing_batch' => $ongoing,
                'upcoming_batch' => $upcoming,
                'ended_batch' => $ended,
            ];
        });

        return response()->json($mapped);
    }

    /**
     * ============================
     * DETAIL PRODUK
     * ============================
     */
    public function detail(Product $product)
    {
        $now = now();

        $product->load(['images', 'categories', 'batches']);

        $onGoingBatch = $product->batches()
            ->where('auction_batches.status', 'published')
            ->where('auction_batches.start_at', '<=', $now)
            ->where('auction_batches.end_at', '>=', $now)
            ->first();

        $nextBatch = !$onGoingBatch
            ? $product->batches()
                ->where('auction_batches.status', 'published')
                ->where('auction_batches.start_at', '>', $now)
                ->orderBy('auction_batches.start_at')
                ->first()
            : null;

        $activeBatch = $onGoingBatch ?: $nextBatch;

        $lots = collect();
        if ($activeBatch) {
            $lots = $product->batchLots()
                ->where('batch_id', $activeBatch->id)
                ->orderBy('lot_number')
                ->get([
                    'id', 'batch_id', 'product_id', 'lot_number',
                    'starting_price', 'reserve_price', 'status'
                ]);
        }

        $meta = null;
        if ($activeBatch) {
            $meta = [
                'batch_id' => $activeBatch->id,
                'title' => $activeBatch->title,
                'status' => $activeBatch->status,
                'start_at' => $activeBatch->start_at,
                'end_at' => $activeBatch->end_at,
                'is_ongoing' => $activeBatch->start_at <= $now && $now <= $activeBatch->end_at,
                'start_in_seconds' => $now->lt($activeBatch->start_at)
                    ? $now->diffInSeconds($activeBatch->start_at)
                    : 0,
                'end_in_seconds' => $now->lt($activeBatch->end_at)
                    ? $now->diffInSeconds($activeBatch->end_at)
                    : 0,
            ];
        }

        return response()->json([
            'product' => $product,
            'active_batch' => $meta,
            'lots' => $lots,
        ]);
    }

    /**
     * INDEX / STORE / SHOW / UPDATE / DESTROY
     * (Tidak diubah)
     */

    public function index(Request $request)
    {
        $query = Product::with(['images', 'categories', 'auctionBatches']);

        // Filter kategori
        if ($request->has('category')) {
            $categorySlug = $request->category;

            $query->whereHas('categories', function($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Search
        if ($request->has('search')) {
            $searchTerm = $request->search;

            $query->where(function($w) use ($searchTerm) {
                $w->where('product_name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        return response()->json(
        $query->paginate($request->get('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seller_id' => 'required|exists:users,id',
            'product_name' => 'required|string|max:255',
            'description' => 'required|string',
            'base_price' => 'required|numeric|min:0',
            'status' => 'required|in:draft,published,archived',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()],422);
        }

        $product = Product::create(
            $request->only(['seller_id','product_name','description','base_price','status'])
        );

        if ($request->has('categories')) {
            $product->categories()->attach($request->categories);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('product_images','public');
                $product->images()->create([
                    'image_url'=>$path,
                    'sort_order'=>$i
                ]);
            }
        }

        $product->load(['images','categories']);

        return response()->json($product,201);
    }

    public function show($id)
    {
        return response()->json(
            Product::with(['images','categories','auctionBatches'])->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'seller_id' => 'sometimes|required|exists:users,id',
            'product_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'base_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|in:draft,published,archived',
            'categories' => 'sometimes|required|array',
            'categories.*' => 'exists:categories,id',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors'=>$validator->errors()],422);
        }

        $product->update(
            $request->only(['seller_id','product_name','description','base_price','status'])
        );

        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        if ($request->hasFile('images')) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image_url);
                $img->delete();
            }

            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('product_images','public');
                $product->images()->create([
                    'image_url'=>$path,
                    'sort_order'=>$i
                ]);
            }
        }

        $product->load(['images','categories']);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->image_url);
        }

        $product->categories()->detach();
        $product->delete();

        return response()->json(['message'=>'Product deleted successfully']);
    }
}
