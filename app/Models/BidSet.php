<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BidSet extends Model
{
    protected $table = 'bid_sets';

    protected $primaryKey = 'id';

    protected $fillable = [
        'batch_id',
        'user_id',
        'submitted_at',
        'status'
    ];

    protected $casts = [
        'submitted_at' => 'datetime'
    ];

    public function batch()
    { 
        return $this->belongsTo(AuctionBatch::class, 'batch_id');
    }

    public function user()
    { 
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function items()
    { 
        return $this->hasMany(BidItem::class, 'bid_set_id');
    }

    public function scopeValid($q){ return $q->where('status','valid'); }
}
}
