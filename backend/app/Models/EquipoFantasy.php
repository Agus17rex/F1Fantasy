<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EquipoFantasy extends Model
{
    protected $table = 'equipos_fantasy';

    protected $fillable = ['usuario_id', 'liga_id', 'nombre', 'presupuesto_restante', 'puntos_totales'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_id');
    }

    public function miembroLiga(): HasOne
    {
        return $this->hasOne(MiembroLiga::class, 'usuario_id', 'usuario_id')
            ->where('liga_id', $this->liga_id);
    }

    // Pilotos activos del equipo
    public function pilotos(): BelongsToMany
    {
        return $this->belongsToMany(Piloto::class, 'equipos_fantasy_pilotos', 'equipo_fantasy_id', 'piloto_id')
            ->withPivot('rol', 'fecha_seleccion')
            ->wherePivotNull('fecha_baja');
    }

    // Escudería activa del equipo
    public function escuderias(): BelongsToMany
    {
        return $this->belongsToMany(Escuderia::class, 'equipos_fantasy_escuderias', 'equipo_fantasy_id', 'escuderia_id')
            ->withPivot('fecha_seleccion')
            ->wherePivotNull('fecha_baja');
    }

    // Coche activo del equipo
    public function coches(): BelongsToMany
    {
        return $this->belongsToMany(Coche::class, 'equipos_fantasy_coches', 'equipo_fantasy_id', 'coche_id')
            ->withPivot('fecha_seleccion')
            ->wherePivotNull('fecha_baja');
    }

    public function puntosPorCarrera(): HasMany
    {
        return $this->hasMany(PuntosEquipoCarrera::class, 'equipo_fantasy_id');
    }

    // ─── Validación ───────────────────────────────────────────────────────────

    public function esValido(): bool
    {
        return $this->pilotos->count() === 2
            && $this->coches->count() === 1
            && $this->escuderias->count() === 1;
    }
}
