<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuntosEquipoCarrera extends Model
{
    protected $table = 'fantasy_team_race_points';

    protected $fillable = ['fantasy_team_id', 'race_id', 'points_earned', 'breakdown'];

    protected function casts(): array
    {
        return ['breakdown' => 'array'];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(EquipoFantasy::class, 'fantasy_team_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'race_id');
    }
}
