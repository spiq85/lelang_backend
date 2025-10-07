<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidItem extends Model
{
    protected $table = 'bid_items';

    protected $primaryKey = 'id';

    protected $fillable = [
        'bid_set_id',
        'lot_id',
        'bid_amount',
        'is_proxy'
    ];
    protected $casts = ['bid_amount' => 'decimal:2','is_proxy'=>'boolean'];

    public function bidSet()
    { 
        return $this->belongsTo(BidSet::class, 'bid_set_id');
    }
    
    public function lot()
    { 
        return $this->belongsTo(BatchLot::class, 'lot_id');
    }
}
