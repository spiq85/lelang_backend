<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function products() {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function auctionBatches() {
        return $this->hasMany(AuctionBatch::class, 'seller_id');
    }

    public function bids() {
        return $this->hasMany(Bid::class, 'user_id');
    }

    public function getFilamentName(): string
    {
        // selalu string; fallback ke email kalau full_name kosong
        return $this->full_name ?: ($this->email ?? 'User');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // atur aturan akses panel kamu di sini
        return (bool) $this->is_active; // atau: return $this->is_active && $this->hasRole('admin');
    }

    // Opsional: biar $user->name tetap ada untuk paket lain
    public function getNameAttribute(): ?string
    {
        return $this->full_name;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
