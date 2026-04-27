<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    protected $table = 'races';

    protected $fillable = [
        'api_id', 'season', 'round', 'name', 'circuit_id',
        'date', 'time', 'is_sprint', 'status', 'transfer_deadline',
    ];

    protected function casts(): array
    {
        return [
            'date'              => 'date',
            'transfer_deadline' => 'datetime',
            'is_sprint'         => 'boolean',
        ];
    }

    public function circuito(): BelongsTo
    {
        return $this->belongsTo(Circuito::class, 'circuit_id');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadoCarrera::class, 'race_id');
    }

    public function puntosEquipos(): HasMany
    {
        return $this->hasMany(PuntosEquipoCarrera::class, 'race_id');
    }

    public function estaProxima(): bool
    {
        return $this->status === 'upcoming' && $this->date->isFuture();
    }

    public function estaPuntuada(): bool
    {
        return $this->status === 'scored';
    }
}
