<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoCarrera extends Model
{
    protected $table = 'resultados_carrera';

    protected $fillable = [
        'carrera_id', 'piloto_id', 'escuderia_id',
        'posicion_salida', 'posicion_final', 'estado',
        'puntos_oficiales', 'vuelta_rapida', 'piloto_del_dia',
        'penalizacion_grid', 'penalizacion_tiempo',
        'posicion_clasificacion', 'puntos_fantasy', 'puntos_calculados',
        'puntos_carrera', 'puntos_qualy',
    ];

    protected function casts(): array
    {
        return [
            'vuelta_rapida'       => 'boolean',
            'piloto_del_dia'      => 'boolean',
            'penalizacion_grid'   => 'boolean',
            'penalizacion_tiempo' => 'boolean',
            'puntos_calculados'   => 'boolean',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function piloto(): BelongsTo
    {
        return $this->belongsTo(Piloto::class, 'piloto_id');
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'escuderia_id');
    }
}
