<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoCarrera extends Model
{
    protected $table = 'race_results';

    protected $fillable = [
        'race_id', 'driver_id', 'constructor_id',
        'grid_position', 'finish_position', 'status',
        'points_official', 'fastest_lap', 'driver_of_the_day',
        'qualifying_position', 'fantasy_points', 'fantasy_points_calculated',
    ];

    protected function casts(): array
    {
        return [
            'fastest_lap'               => 'boolean',
            'driver_of_the_day'         => 'boolean',
            'fantasy_points_calculated' => 'boolean',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'race_id');
    }

    public function piloto(): BelongsTo
    {
        return $this->belongsTo(Piloto::class, 'driver_id');
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'constructor_id');
    }
}
