<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchLot extends Model
{
    protected $table = 'batch_lots';

    protected $fillable = [
        'batch_id',
        'lot_number',
        'starting_price',
        'reserve_price',
        'status',
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'reserve_price'  => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(AuctionBatch::class, 'batch_id');
    }

    // Alias biar lebih pendek di form/table
    public function products()
    {
        return $this->lotProducts();
    }

    // RELASI BARU: satu lot bisa banyak produk
    public function lotProducts()
    {
        return $this->hasMany(BatchLotProduct::class, 'batch_lot_id')
            ->with('product')
            ->orderBy('id');
    }
    public function bidItems()
    {
        return $this->hasMany(BidItem::class, 'lot_id');
    }

    public function winner()
    {
        return $this->hasOne(LotWinner::class, 'lot_id');
    }
}
