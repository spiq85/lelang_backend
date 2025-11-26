<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';
    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'position',
        'status',
        'start_at',
        'end_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        
        return $query
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('position');
    }
    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', 'active')
            ->orWhere('end_at', '<', now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (!$this->start_at || $this->start_at->isPast()) &&
            (!$this->end_at || $this->end_at->isFuture());
    }
}
