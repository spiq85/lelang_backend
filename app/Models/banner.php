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

    // Scope aktif — sementara matikan cek waktu (nanti di-enable lagi kalau sudah fix datetime)
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    // Accessor image_url — KUNCI SUPAYA GAMBAR MUNCUL DI VITE
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) return null;

        // DEVELOPMENT ONLY → pakai proxy Vite (localhost:5173)
        if (app()->environment('local')) {
            // http://localhost:5173/storage/banners/xxx.jpg
            return url('/storage/' . $this->image_path);
            // atau kalau mau lebih eksplisit:
            // return 'http://localhost:5173/storage/' . $this->image_path;
        }

        // Production → pakai yang normal
        return Storage::disk('public')->url($this->image_path);
    }

    protected $appends = ['image_url'];
}

