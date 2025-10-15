<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AuctionBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuctionBatchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seller1 = User::where('role','seller')->where('email','seller@example.com')->first();
        $seller2 = User::where('role','seller')->where('email','seller2@example.com')->first();
        $admin = User::where('role','admin')->first();

        $now = now();
        $batches = [
            [
                'seller_id'          => $seller1->id,
                'title'              => 'Weekend Auto Fair',
                'description'        => 'Lelang mobil & motor pilihan.',
                'start_at'           => $now->copy()->subHours(1),
                'end_at'             => $now->copy()->addHours( 5),
                'bid_increment_rule' => ['type'=>'flat','step'=>100_000],
                'reserve_rule'       => ['mode'=>'undisclosed'],
                'status'             => 'published', // langsung published buat testing
                'created_by'         => $admin?->id,
            ],
            [
                'seller_id'          => $seller2->id,
                'title'              => 'Midweek Clearance',
                'description'        => 'Unit pilihan, cepat & hemat.',
                'start_at'           => $now->copy()->addDays(2),
                'end_at'             => $now->copy()->addDays(6),
                'bid_increment_rule' => ['type'=>'tiered','steps'=>[
                    ['lt'=>10_000_000,'step'=>50_000],
                    ['lt'=>50_000_000,'step'=>100_000],
                    ['lte'=>200_000_000,'step'=>250_000],
                ]],
                'reserve_rule'       => ['mode'=>'none'],
                'status'             => 'published',
                'created_by'         => $admin?->id,
            ],
        ];

        foreach($batches as $b) {
            AuctionBatch::firstOrCreate(
                [
                    'seller_id' => $b['seller_id'],
                    'title' => $b['title'],
                    'start_at' => $b['start_at'],
                    'end_at' => $b['end_at'],
                ],
                [
                    'description' => $b['description'],
                    'bid_increment_rule' => $b['bid_increment_rule'],
                    'reserve_rule' => $b['reserve_rule'],
                    'status' => $b['status'],
                    'created_by' => $b['created_by'],
                ]
            );
        }
    }
}
