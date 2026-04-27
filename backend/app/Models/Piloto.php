<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piloto extends Model
{
    protected $table = 'drivers';

    protected $fillable = [
        'api_id', 'code', 'number', 'first_name', 'last_name',
        'nationality', 'date_of_birth', 'photo', 'constructor_id', 'price', 'is_active', 'es_reserva',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active'     => 'boolean',
            'es_reserva'    => 'boolean',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return number_format($this->price / 1_000_000, 1) . 'M';
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'constructor_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'driver_id');
    }
}
