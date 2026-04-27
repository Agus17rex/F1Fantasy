<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Piloto extends Model
{
    protected $table = 'pilotos';

    protected $fillable = [
        'api_id', 'codigo', 'numero', 'nombre', 'apellido',
        'nacionalidad', 'fecha_nacimiento', 'foto', 'escuderia_id',
        'precio', 'activo', 'es_reserva',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo'           => 'boolean',
            'es_reserva'       => 'boolean',
        ];
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return number_format($this->precio / 1_000_000, 1) . 'M';
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'escuderia_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'piloto_id');
    }
}
