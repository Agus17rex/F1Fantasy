<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Escuderia extends Model
{
    protected $table = 'escuderias';

    protected $fillable = [
        'api_id', 'nombre', 'nacionalidad', 'logo', 'color', 'precio', 'activa',
    ];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return number_format($this->precio / 1_000_000, 1) . 'M';
    }

    public function pilotos(): HasMany
    {
        return $this->hasMany(Piloto::class, 'escuderia_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'escuderia_id');
    }
}
