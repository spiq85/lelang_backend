<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'npwp',
        'phone_number',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================
    // 🔹 Relationships
    // =====================
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function auctionBatches()
    {
        return $this->hasMany(AuctionBatch::class, 'seller_id');
    }

    public function bids()
    {
        return $this->hasMany(BidSet::class, 'user_id');
    }

    // =====================
    // 🔹 Filament integration
    // =====================
    public function getFilamentName(): string
    {
        // harus selalu mengembalikan string, tidak boleh null
        return $this->full_name ?: ($this->email ?? 'User');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // misal, hanya user aktif yang bisa login
        return (bool) $this->is_active;
    }

    // Opsional fallback untuk kompatibilitas paket lain
    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
