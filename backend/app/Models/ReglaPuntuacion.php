<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaPuntuacion extends Model
{
    protected $table = 'reglas_puntuacion';

    protected $fillable = ['evento', 'puntos', 'descripcion', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }
}
