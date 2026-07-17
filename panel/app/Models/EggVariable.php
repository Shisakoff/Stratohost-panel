<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'egg_id', 'name', 'env_variable', 'description', 'default_value',
    'rules', 'user_viewable', 'user_editable',
])]
class EggVariable extends Model
{
    protected function casts(): array
    {
        return [
            'user_viewable' => 'boolean',
            'user_editable' => 'boolean',
        ];
    }

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }
}
