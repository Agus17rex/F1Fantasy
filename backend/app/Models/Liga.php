<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liga extends Model
{
    protected $table = 'ligas';

    protected $fillable = [
        'nombre', 'codigo', 'descripcion', 'propietario_id',
        'max_miembros', 'es_privada', 'temporada', 'estado', 'presupuesto_inicial',
    ];

    protected function casts(): array
    {
        return ['es_privada' => 'boolean'];
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

    public function miembros(): HasMany
    {
        return $this->hasMany(MiembroLiga::class, 'liga_id');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(EquipoFantasy::class, 'liga_id');
    }
}
