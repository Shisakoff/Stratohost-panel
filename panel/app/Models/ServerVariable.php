<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['server_id', 'egg_variable_id', 'value'])]
class ServerVariable extends Model
{
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function eggVariable(): BelongsTo
    {
        return $this->belongsTo(EggVariable::class);
    }
}
