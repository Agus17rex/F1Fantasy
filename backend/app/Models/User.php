<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'avatar', 'total_points', 'budget',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(EquipoFantasy::class, 'user_id');
    }

    public function ligas(): HasManyThrough
    {
        return $this->hasManyThrough(Liga::class, MiembroLiga::class, 'user_id', 'id', 'id', 'league_id');
    }

    public function membresiaLigas(): HasMany
    {
        return $this->hasMany(MiembroLiga::class, 'user_id');
    }

    public function ligasPropias(): HasMany
    {
        return $this->hasMany(Liga::class, 'owner_id');
    }
}
