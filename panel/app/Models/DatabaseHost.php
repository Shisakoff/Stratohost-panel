<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'host', 'port', 'username', 'password', 'max_databases'])]
// Same reasoning as Node::daemon_token: the encrypted cast decrypts back
// to plaintext on read, so it must never be serialized to JSON.
#[Hidden(['password'])]
class DatabaseHost extends Model
{
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function serverDatabases(): HasMany
    {
        return $this->hasMany(ServerDatabase::class);
    }

    public function dsn(): string
    {
        return "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
    }
}
