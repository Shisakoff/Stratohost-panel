<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nest_id', 'name', 'description', 'docker_image', 'startup', 'stop_command',
    'install_image', 'install_entrypoint', 'install_script',
])]
class Egg extends Model
{
    public function nest(): BelongsTo
    {
        return $this->belongsTo(Nest::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(EggVariable::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }
}
