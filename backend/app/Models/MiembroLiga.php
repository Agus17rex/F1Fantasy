<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MiembroLiga extends Model
{
    protected $table = 'league_members';

    protected $fillable = ['league_id', 'user_id', 'total_points', 'rank', 'joined_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function liga(): BelongsTo
    {
        return $this->belongsTo(Liga::class, 'league_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function equipo(): HasOne
    {
        return $this->hasOne(EquipoFantasy::class, 'user_id', 'user_id')
            ->where('league_id', $this->league_id);
    }
}
