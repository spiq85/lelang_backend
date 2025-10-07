<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roots = [
            'Kendaraan',
            'Elektronik',
            'Properti',
            'Fashion',
        ];

        $rootIds = [];
        foreach($roots as $name) {
            $root = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name,'parent_id' => null]
            );
            $rootIds[$name] = $root->id;
        }

        $children = [
            ['name' => 'Mobil',     'parent' => 'Kendaraan'],
            ['name' => 'Motor',     'parent' => 'Kendaraan'],
            ['name' => 'Truk',     'parent' => 'Kendaraan'],
            ['name' => 'SUV',     'parent' => 'Kendaraan'],
            ['name' => 'MPV',     'parent' => 'Kendaraan'],
        ];

        foreach($children as $c) {
            Category::firstOrCreate(
                ['slug' => Str::slug($c['parent']. ' '.$c['name'])],
                ['name' => $c['name'], 'parent_id' => $rootIds[$c['parent']]]
            );
        }
    }
}
