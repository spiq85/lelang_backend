<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchLot extends Model
{
    protected $table = 'batch_lots';

    protected $primaryKey = 'id';

    protected $fillable = [
        'batch_id','product_id','lot_number','starting_price','reserve_price','status'
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'reserve_price'  => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(AuctionBatch::class, 'batch_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
