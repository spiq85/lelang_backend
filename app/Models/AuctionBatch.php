<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Notifications\AuctionBatchStatusNotification;
use Attribute;

class AuctionBatch extends Model
{
    protected $table = 'auction_batches';

    protected $primaryKey = 'id';

    protected $fillable = [
        'seller_id',
        'title',
        'description',
        'bid_increment_rule',
        'reserve_rule',
        'status',
        'created_by',
        'start_at',
        'end_at',
        'approved_by',
        'approved_at',
        'review_note',
    ];

    protected $casts = [
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
        'approved_at'       => 'datetime',
        'bid_increment_rule' => 'array',
        'reserve_rule'      => 'array',
        'status'            => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Booted Events
    |--------------------------------------------------------------------------
    */
    protected static function booted(): void
    {
        // Otomatis buat 1 lot kosong (placeholder) saat batch dibuat
        static::created(function (self $batch) {
            $batch->lots()->create([
                'lot_number'     => 1,
                'starting_price' => 0,
                'reserve_price'  => null,
                'status'         => 'open',
                // product_id sengaja dikosongkan (null) → akan diisi nanti
            ]);
        });

        // Kirim notifikasi setiap kali status batch berubah
        static::updated(function (self $batch) {
            if (!$batch->wasChanged('status')) return;

            match ($batch->status) {
                'pending_review' => self::notifyAdminsPendingReview($batch),
                'published'      => self::notifySellerApproved($batch),
                'cancelled'      => self::notifySellerRejected($batch),
                default          => null,
            };
        });
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = strtolower($value);
    }

    public function getStatusAttribute($value)
    {
        return strtolower($value);
    }

    /*
    |--------------------------------------------------------------------------
    | Notification Helpers
    |--------------------------------------------------------------------------
    */
    protected static function notifyAdminsPendingReview(self $batch): void
    {
        $admins = \App\Models\User::role('super_admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new AuctionBatchStatusNotification(
                'Pending Review',
                "Seller {$batch->seller->full_name} mengirim batch \"{$batch->title}\" untuk direview.",
                $batch->id,
            ));
        }
    }

    protected static function notifySellerApproved(self $batch): void
    {
        $batch->seller->notify(new AuctionBatchStatusNotification(
            'Approved',
            "Batch \"{$batch->title}\" sudah disetujui oleh admin.",
            $batch->id,
        ));
    }

    protected static function notifySellerRejected(self $batch): void
    {
        $batch->seller->notify(new AuctionBatchStatusNotification(
            'Rejected',
            "Batch \"{$batch->title}\" ditolak oleh admin.",
            $batch->id,
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

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

   public function products()
    {
        return $this->belongsToMany(Product::class, 'batch_lots', 'batch_id', 'product_id')
                    ->withPivot('lot_number', 'starting_price', 'reserve_price', 'status')
                    ->withTimestamps()
                    ->with(['coverImage', 'seller']);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes & State Helpers
    |--------------------------------------------------------------------------
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function canSendToReview(): bool
    {
        return in_array($this->status, ['draft', 'cancelled']);
    }

    public function canApprove(): bool
    {
        return $this->status === 'pending_review';
    }

    public function canReject(): bool
    {
        return $this->status === 'pending_review';
    }

    public function canPublish(): bool
    {
        return $this->status === 'pending_review';
    }

    public function canClose(): bool
    {
        return $this->status === 'published';
    }

    public function getPhaseAttribute(): string
    {
        $now = now();

        if ($this->status === 'closed' || ($this->end_at && $now->gte($this->end_at))) {
            return 'ended';
        }

        if ($this->status === 'published') {
            if ($this->start_at && $now->lt($this->start_at)) return 'scheduled';
            return 'live';
        }

        return $this->status;
    }

    public function getStartsInSecondsAttribute(): ?int
    {
        return ($this->phase === 'scheduled' && $this->start_at)
            ? now()->diffInSeconds($this->start_at, false)
            : null;
    }

    public function getEndsInSecondsAttribute(): ?int
    {
        return ($this->phase === 'live' && $this->end_at)
            ? now()->diffInSeconds($this->end_at, false)
            : null;
    }

    public function getProgressPercentAttribute(): ?float
    {
        if ($this->phase !== 'live' || !$this->start_at || !$this->end_at) return null;

        $total = $this->start_at->diffInSeconds($this->end_at, false);

        if ($total <= 0) return 100.0;
        $elapsed = $this->start_at->diffInSeconds(now(), false);

        return max(0, min(100, ($elapsed / $total) * 100));
    }

    public function batchLotProducts()
    {
        return $this->hasManyThrough(
            BatchLotProduct::class,
            BatchLot::class,
            'batch_id',      // Foreign key di batch_lots
            'batch_lot_id',  // Foreign key di batch_lot_products
            'id',            // Local key di auction_batches
            'id'             // Local key di batch_lots
        );
    }
}
