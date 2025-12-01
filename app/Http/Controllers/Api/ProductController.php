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
            ->where('products.status', 'published')
            ->whereHas('batchLots.batch', function ($b) use ($now) {
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
            $query->where(function ($w) use ($term) {
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
            ->where('status', 'published')
            ->with([
                'images',
                'categories',
                'batches' => function ($query) {
                    $query->select([
                        'auction_batches.id',
                        'auction_batches.title',
                        'auction_batches.start_at',
                        'auction_batches.end_at',
                        'auction_batches.status',
                        // WAJIB TAMBAH INI! Karena many-to-many via pivot
                        'batch_lots.batch_id',
                        'batch_lots.product_id',
                        'batch_lots.lot_number',
                        'batch_lots.starting_price',
                        'batch_lots.reserve_price',
                    ])
                        ->where('auction_batches.status', 'published');
                }
            ])
            ->select('id', 'product_name', 'description', 'base_price', 'created_at', 'status')
            ->latest('created_at')
            ->paginate($request->get('per_page', 12));

        // Transform collection tanpa N+1
        $products->getCollection()->transform(function ($product) use ($now) {
            $batches = $product->batches;

            $ongoing = $batches->first(function ($batch) use ($now) {
                return $batch->start_at <= $now && $now <= $batch->end_at;
            });

            $upcoming = $batches->where('start_at', '>', $now)
                ->sortBy('start_at')
                ->first();

            $ended = $batches->where('end_at', '<', $now)
                ->sortByDesc('end_at')
                ->first();

            return [
                'id'            => $product->id,
                'product_name'  => $product->product_name,
                'description'   => $product->description,
                'base_price'    => $product->base_price,
                'images'        => $product->images,
                'categories'    => $product->categories,

                'auction_status' => $ongoing ? 'ongoing'
                    : ($upcoming ? 'upcoming'
                        : ($ended ? 'ended' : 'no_auction')),

                'ongoing_batch'  => $ongoing ? [
                    'id'        => $ongoing->id,
                    'title'     => $ongoing->title,
                    'start_at'  => $ongoing->start_at,
                    'end_at'    => $ongoing->end_at,
                    'lot_number' => $ongoing->pivot->lot_number ?? null,
                    'starting_price' => $ongoing->pivot->starting_price ?? null,
                ] : null,

                'upcoming_batch' => $upcoming ? [
                    'id'        => $upcoming->id,
                    'title'     => $upcoming->title,
                    'start_at'  => $upcoming->start_at,
                    'lot_number' => $upcoming->pivot->lot_number ?? null,
                ] : null,

                'ended_batch'    => $ended ? [
                    'id'        => $ended->id,
                    'title'     => $ended->title,
                    'end_at'    => $ended->end_at,
                ] : null,
            ];
        });

        return response()->json($products);
    }
    /**
     * ============================
     * DETAIL PRODUK
     * ============================
     */
    public function detail(Product $product)
    {
        $now = now();

        // Load relasi dengan pivot yang benar
        $product->load([
            'images',
            'categories',
            'batches' => function ($q) {
                $q->withPivot('lot_number', 'starting_price', 'reserve_price', 'status')
                    ->where('auction_batches.status', 'published');
            }
        ]);

        // Cari batch yang SEDANG BERLANGSUNG atau UPCOMING
        $activeBatch = $product->batches->first(function ($batch) use ($now) {
            return $batch->status === 'published' &&
                $batch->start_at && $batch->end_at;
        });

        // Kalau nggak ada batch published sama sekali
        if (!$activeBatch) {
            return response()->json([
                'product'      => $product,
                'active_batch' => null,
                'lots'         => null,
            ]);
        }

        // Tentukan phase
        $isOngoing = $activeBatch->start_at <= $now && $now <= $activeBatch->end_at;
        $isUpcoming = $now < $activeBatch->start_at;
        // $isEnded = $now > $activeBatch->end_at; (nggak perlu, karena kita ambil yang terdekat)

        // Ambil lot dari batch ini
        $lot = $product->batchLots()
            ->where('batch_id', $activeBatch->id)
            ->first([
                'id',
                'lot_number',
                'starting_price',
                'reserve_price',
                'status'
            ]);

        $meta = [
            'batch_id'        => $activeBatch->id,
            'title'           => $activeBatch->title,
            'start_at'        => $activeBatch->start_at,
            'end_at'          => $activeBatch->end_at,
            'status'          => $activeBatch->status,
            'phase'           => $isOngoing ? 'running' : ($isUpcoming ? 'upcoming' : 'ended'),
            'lot_number'      => $lot?->lot_number,
            'starting_price'  => $lot?->starting_price ?? $product->base_price,
            'reserve_price'   => $lot?->reserve_price,
        ];

        return response()->json([
            'product'      => $product,
            'active_batch' => $meta,
            'lots'         => $lot ? [$lot] : [],
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

            $query->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        // Search
        if ($request->has('search')) {
            $searchTerm = $request->search;

            $query->where(function ($w) use ($searchTerm) {
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create(
            $request->only(['seller_id', 'product_name', 'description', 'base_price', 'status'])
        );

        if ($request->has('categories')) {
            $product->categories()->attach($request->categories);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('product_images', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $i
                ]);
            }
        }

        $product->load(['images', 'categories']);

        return response()->json($product, 201);
    }

    public function show($id)
    {
        return response()->json(
            Product::with(['images', 'categories', 'auctionBatches'])->findOrFail($id)
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update(
            $request->only(['seller_id', 'product_name', 'description', 'base_price', 'status'])
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
                $path = $image->store('product_images', 'public');
                $product->images()->create([
                    'image_url' => $path,
                    'sort_order' => $i
                ]);
            }
        }

        $product->load(['images', 'categories']);

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

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
