<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seller1 = User::where('role','seller')->where('email','seller@example.com')->first();
        $seller2 = User::where('role','seller')->where('email','seller2@example.com')->first();

        $products = [
            [
                'seller_id' => $seller1->id,
                'product_name' => 'Toyota Avanza G 2019',
                'description' => 'Tangan pertama, servis rutin.',
                'base_price' => 120000000,
                'status' => 'published',
            ],
            [
                'seller_id' => $seller1->id,
                'product_name' => 'Honda Brio RS 2020',
                'description' => 'Tangan pertama, servis rutin.',
                'base_price' => 125000000,
                'status' => 'published',
            ],
            [
                'seller_id' => $seller1->id,
                'product_name' => 'Honda Civic RS Turbo 2023',
                'description' => 'Second.',
                'base_price' => 435000000,
                'status' => 'published',
            ],
            [
                'seller_id' => $seller2->id,
                'product_name' => 'Suzuki Ertiga GX 2018',
                'description' => 'Pajak Hidup.',
                'base_price' => 100000000,
                'status' => 'published',
            ],
            [
                'seller_id' => $seller2->id,
                'product_name' => 'Yamaha R6 2012',
                'description' => 'Second, Pajak Hidup.',
                'base_price' => 300000000,
                'status' => 'published',
            ],
            [
                'seller_id' => $seller2->id,
                'product_name' => 'Yamaha NMAX 155 2021',
                'description' => 'Unit terawatl, part ori',
                'base_price' => 28000000,
                'status' => 'published',
            ],
        ];

        foreach($products as $p) {
            Product::firstOrCreate(
                ['seller_id' => $p['seller_id'], 'product_name' => $p['product_name']],
                [
                    'description' => $p['description'],
                    'base_price' => $p['base_price'],
                    'status' => $p['status'],
                ]
            );
        }
    }
}
