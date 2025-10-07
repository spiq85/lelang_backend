<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $map = [
            'avanza' => 'kendaraan suv',
            'brio' => 'kendaraan mobil',
            'civic' => 'kendaraan mpv',
            'ertiga' => 'kendaraan mpv',
            'nmax' => 'kendaraan motor',
            'r6' => 'kendaraan motor',
        ];

        $products = Product::all();
        foreach($products as $product) {
            $name = Str::lower($product->produc_name);
            $choosenSlug = null;

            foreach($map as $keyword => $slug) {
                if(Str::contains($name,$keyword)) {
                    $choosenSlug = Str::slug($slug);
                    break;
                }
            }

            if(!$choosenSlug) {
                $choosenSlug = 'kendaraan';
            }

            $category = Category::where('slug', $choosenSlug)->first()
            ?: Category::where('slug','kendaraan')->first();

            if($category) {
                ProductCategory::firstOrCreate(
                    ['product_id' => $product->id, 'category_id' => $category->id],
                    ['note' => null]
                );
            }
        }
    }
}
