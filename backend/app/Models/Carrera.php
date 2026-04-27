<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'carreras';

    protected $fillable = [
        'api_id', 'temporada', 'ronda', 'nombre', 'circuito_id',
        'fecha', 'hora', 'es_sprint', 'estado', 'cierre_mercado',
    ];

    protected function casts(): array
    {
        return [
            'fecha'          => 'date',
            'cierre_mercado' => 'datetime',
            'es_sprint'      => 'boolean',
        ];
    }

    public function circuito(): BelongsTo
    {
        return $this->belongsTo(Circuito::class, 'circuito_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'carrera_id');
    }

    public function puntosEquipos(): HasMany
    {
        return $this->hasMany(PuntosEquipoCarrera::class, 'carrera_id');
    }

    public function estaProxima(): bool
    {
        return $this->estado === 'upcoming' && $this->fecha->isFuture();
    }

    public function estaPuntuada(): bool
    {
        return $this->estado === 'scored';
    }
}
