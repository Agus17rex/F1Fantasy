<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MiembroLiga extends Model
{
    protected $table = 'miembros_liga';

    protected $fillable = ['liga_id', 'usuario_id', 'puntos_totales', 'posicion', 'fecha_union'];

    protected function casts(): array
    {
        return ['fecha_union' => 'datetime'];
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'liga_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function equipo(): HasOne
    {
        return $this->hasOne(EquipoFantasy::class, 'usuario_id', 'usuario_id')
            ->where('liga_id', $this->liga_id);
    }
}
