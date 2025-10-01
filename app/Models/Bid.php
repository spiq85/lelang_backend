<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Bid extends Model
{
    protected $table = 'bids';

    protected $primaryKey = 'id';

    protected $fillable = [
        'batch_id','user_id','bid_amount','submitted_at','status'
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function batch() { return $this->belongsTo(AuctionBatch::class, 'batch_id'); }
    public function user()  { return $this->belongsTo(User::class, 'user_id'); }

    public function scopeIsValid(Builder $q){ return $q->where('status','valid'); }

    public function scopeHighestForBatch(Builder $q, int $batchId){
        return $q->valid()
            ->where('batch_id', $batchId)
            ->orderByDesc('bid_amount')
            ->orderBy('submitted_at'); // tie-break: paling cepat menang
    }

}
