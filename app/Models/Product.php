<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id';

    protected $fillable = [
        'seller_id',
        'product_name',
        'description',
        'base_price',
        'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function seller() {
        return $this->belongsTo(User::class,'seller_id');
    }

    public function images() {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function batchLots() {
        return $this->hasMany(BatchLot::class, 'product_id');
    }

    public function batches() {
        return $this->belongsToMany(AuctionBatch::class, 
        'batch_lots', 
        'product_id', 
        'batch_id')->withPivot(['lot_number', 'starting_price', 'reserve_price', 'status'])->withTimestamps();
    }

    public function coverImage() {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order');
    }
}
