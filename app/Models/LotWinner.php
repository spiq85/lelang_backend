<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LotWinner extends Model
{
    protected $table = 'lot_winners';
    
    protected $primaryKey = 'id';

    protected $fillable = [
        'lot_id',
        'winner_user_id',
        'winning_bid_amount',
        'choosen_by',
        'reason',
        'decided_at',
    ];

    protected $cast = [
        'winning_bid_amount' => 'decimal:2',
        'decided_at' => 'datetime'
    ];

    public function lot() 
    {
        return $this->belongsTo(BatchLot::class, 'lot_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function chooser()
    {
        return $this->belongsTo(User::class, 'choosen_by');
    }
}
