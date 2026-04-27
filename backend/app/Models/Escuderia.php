<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Escuderia extends Model
{
    protected $table = 'constructors';

    protected $fillable = [
        'api_id', 'name', 'nationality', 'logo', 'color', 'price', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return number_format($this->price / 1_000_000, 1) . 'M';
    }

    public function pilotos(): HasMany
    {
        return $this->hasMany(Piloto::class, 'constructor_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'constructor_id');
    }
}
