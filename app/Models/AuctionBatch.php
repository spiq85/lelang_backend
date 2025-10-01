<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AuctionBatch extends Model
{
    protected $fillable = [
        'seller_id','product_id','title','description',
        'bid_increment_rule','reserve_rule',
        'starting_price','reserve_price','status','created_by'
    ];

    protected $casts = [
        'starting_price' => 'decimal:2',
        'reserve_price'  => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function bids()    { return $this->hasMany(Bid::class, 'batch_id'); }

    // Scopes
    public function scopeActive(Builder $q){ return $q->where('status','active'); }
    public function scopeClosed(Builder $q){ return $q->where('status','closed'); }

    // Helpers: parse rule (karena disimpan text)
    public function getBidIncrementRuleArrayAttribute(): array {
        $txt = $this->bid_increment_rule;
        $data = json_decode($txt, true);
        return is_array($data) ? $data : ['type' => 'flat', 'step' => 0];
    }
}
