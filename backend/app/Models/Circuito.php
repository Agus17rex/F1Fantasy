<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Circuito extends Model
{
    protected $table = 'circuits';

    protected $fillable = ['api_id', 'name', 'location', 'country', 'lat', 'lng', 'image'];

    public function carreras(): HasMany
    {
        return $this->hasMany(Carrera::class, 'circuit_id');
    }
}
