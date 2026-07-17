<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description'])]
class Nest extends Model
{
    public function eggs(): HasMany
    {
        return $this->hasMany(Egg::class);
    }
}
