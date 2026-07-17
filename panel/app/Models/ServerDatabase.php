<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['server_id', 'database_host_id', 'database', 'username', 'password', 'remote'])]
// Unlike Node.daemon_token or DatabaseHost.password, this one is NOT
// hidden: the admin legitimately needs to read it again later to plug
// into a game server's plugin/CMS config, same as Pterodactyl's own
// Databases tab.
class ServerDatabase extends Model
{
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function databaseHost(): BelongsTo
    {
        return $this->belongsTo(DatabaseHost::class);
    }
}
