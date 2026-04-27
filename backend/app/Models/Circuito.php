<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Circuito extends Model
{
    protected $table = 'circuitos';

    protected $fillable = ['api_id', 'nombre', 'ubicacion', 'pais', 'lat', 'lng', 'imagen'];

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'circuito_id');
    }
}
