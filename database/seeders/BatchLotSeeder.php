<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuctionBatch;
use App\Models\Product;
use App\Models\BatchLot;

class BatchLotSeeder extends Seeder
{
    public function run(): void
    {
        $batch1 = AuctionBatch::where('title','Weekend Auto Fair')->first();
        $batch2 = AuctionBatch::where('title','Midweek Clearance')->first();

        $products = Product::orderBy('id')->get();
        if ($products->count() < 4) return;

        // Assign 2 produk ke batch1, 2 produk ke batch2 (contoh)
        $p1 = $products[0];
        $p2 = $products[1];
        $p3 = $products[2];
        $p4 = $products[3];

        if ($batch1) {
            BatchLot::firstOrCreate(
                ['batch_id' => $batch1->id, 'lot_number' => 1],
                [
                    'product_id'     => $p1->id,
                    'starting_price' => max(1, (float)$p1->base_price * 0.8),
                    'reserve_price'  => null,
                    'status'         => 'open',
                ]
            );

            BatchLot::firstOrCreate(
                ['batch_id' => $batch1->id, 'lot_number' => 2],
                [
                    'product_id'     => $p2->id,
                    'starting_price' => max(1, (float)$p2->base_price * 0.8),
                    'reserve_price'  => (float)$p2->base_price * 0.9,
                    'status'         => 'open',
                ]
            );
        }

        if ($batch2) {
            BatchLot::firstOrCreate(
                ['batch_id' => $batch2->id, 'lot_number' => 1],
                [
                    'product_id'     => $p3->id,
                    'starting_price' => max(1, (float)$p3->base_price * 0.75),
                    'reserve_price'  => null,
                    'status'         => 'open',
                ]
            );

            BatchLot::firstOrCreate(
                ['batch_id' => $batch2->id, 'lot_number' => 2],
                [
                    'product_id'     => $p4->id,
                    'starting_price' => max(1, (float)$p4->base_price * 0.75),
                    'reserve_price'  => null,
                    'status'         => 'open',
                ]
            );
        }
    }
}
