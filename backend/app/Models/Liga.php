<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liga extends Model
{
    protected $table = 'leagues';

    protected $fillable = [
        'name', 'code', 'description', 'owner_id',
        'max_members', 'is_private', 'season', 'status', 'presupuesto_inicial',
    ];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function miembros(): HasMany
    {
        return $this->hasMany(MiembroLiga::class, 'league_id');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(EquipoFantasy::class, 'league_id');
    }
}
