<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipoFantasy extends Model
{
    protected $table = 'fantasy_teams';

    protected $fillable = ['user_id', 'league_id', 'name', 'remaining_budget', 'total_points'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'league_id');
    }

    public function miembroLiga(): HasOne
    {
        return $this->hasOne(MiembroLiga::class, 'user_id', 'user_id')
            ->where('league_id', $this->league_id);
    }

    // Pilotos activos (titular / banquillo)
    public function pilotos(): BelongsToMany
    {
        return $this->belongsToMany(Piloto::class, 'fantasy_team_drivers', 'fantasy_team_id', 'driver_id')
            ->withPivot('role', 'selected_at')
            ->wherePivotNull('removed_at');
    }

    // Escudería activa del equipo
    public function escuderias(): BelongsToMany
    {
        return $this->belongsToMany(Escuderia::class, 'fantasy_team_constructors', 'fantasy_team_id', 'constructor_id')
            ->withPivot('selected_at')
            ->wherePivotNull('removed_at');
    }

    // Director activo del equipo
    public function directores(): BelongsToMany
    {
        return $this->belongsToMany(DirectorEquipo::class, 'fantasy_team_principals', 'fantasy_team_id', 'team_principal_id')
            ->withPivot('selected_at')
            ->wherePivotNull('removed_at');
    }

    public function puntosPorCarrera(): HasMany
    {
        return $this->hasMany(PuntosEquipoCarrera::class, 'fantasy_team_id');
    }

    // ─── Validación ───────────────────────────────────────────────────────────

    public function esValido(): bool
    {
        return $this->pilotos->count() === 3
            && $this->directores->count() === 1
            && $this->escuderias->count() === 1;
    }
}
