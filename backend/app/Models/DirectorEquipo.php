<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectorEquipo extends Model
{
    protected $table = 'team_principals';

    protected $fillable = [
        'name', 'nationality', 'photo', 'constructor_id', 'price', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function escuderia(): BelongsTo
    {
        return $this->belongsTo(Escuderia::class, 'constructor_id');
    }
}
