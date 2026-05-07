<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coche extends Model
{
    protected $table = 'coches_equipo';

    protected $fillable = [
        'nombre', 'foto', 'escuderia_id', 'precio', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'escuderia_id');
    }
}
