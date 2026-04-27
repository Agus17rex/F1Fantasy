<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaPuntuacion extends Model
{
    protected $table = 'scoring_rules';

    protected $fillable = ['event', 'points', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
