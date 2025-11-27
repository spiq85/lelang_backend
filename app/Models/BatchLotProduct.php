<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchLotProduct extends Model
{
    protected $table = 'batch_lot_products';

    protected $fillable = [
        'batch_lot_id',
        'product_id',
        'starting_price',
        'reserve_price',
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'reserve_price'  => 'decimal:2',
    ];

    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLot::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->with('images');
    }
}