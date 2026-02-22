<?php   

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $fillable = [
        'title','subtitle','image_path','position','status',
        'start_at','end_at','created_by','updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;

        if (app()->environment('local')) {
            return url('/storage/' . $this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }

    protected $appends = ['image_url'];
}

