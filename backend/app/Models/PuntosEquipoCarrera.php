<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuntosEquipoCarrera extends Model
{
    protected $table = 'puntos_equipo_carrera';

    protected $fillable = ['equipo_fantasy_id', 'carrera_id', 'puntos_obtenidos', 'desglose'];

    protected function casts(): array
    {
        return ['desglose' => 'array'];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(EquipoFantasy::class, 'equipo_fantasy_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }
}
