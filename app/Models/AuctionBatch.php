<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AuctionBatch extends Model
{
    protected $table = 'auction_batches';

    protected $primayKey = 'id';

    protected $fillable = [
        'seller_id','title','description',
        'bid_increment_rule','reserve_rule',
        'status','created_by','start_at','end_at'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
        'bid_increment_rule' => 'array', // karena kolom json
        'reserve_rule'       => 'array', // karena kolom json
    ];

    public function seller()  
    { 
        return $this->belongsTo(User::class, 'seller_id');
    }
    
    public function creator()
    { 
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function lots()    
    { 
        return $this->hasMany(BatchLot::class, 'batch_id')->orderBy('lot_number');
    }

    public function bidSets() 
    { 
        return $this->hasMany(BidSet::class, 'batch_id');
    }

    public function scopeActive($q){ return $q->where('status','active'); }
}