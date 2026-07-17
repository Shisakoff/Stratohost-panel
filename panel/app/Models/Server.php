<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'description', 'owner_id', 'node_id', 'allocation_id', 'egg_id',
    'startup', 'memory', 'swap', 'disk', 'cpu', 'status',
])]
class Server extends Model
{
    /**
     * Servers are addressed by uuid in routes and in the agent's own API,
     * not by the internal auto-increment id.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            $server->uuid ??= (string) Str::uuid();
        });

        // Keep allocations.server_id (used to compute which ports are free
        // on a node) in sync with servers.allocation_id (the server's own
        // pointer to its primary allocation) - the schema tracks the
        // relationship in both directions, so both ends need updating.
        static::created(function (Server $server) {
            Allocation::whereKey($server->allocation_id)->update(['server_id' => $server->id]);
        });

        static::updated(function (Server $server) {
            if (! $server->wasChanged('allocation_id')) {
                return;
            }

            Allocation::whereKey($server->allocation_id)->update(['server_id' => $server->id]);

            $previousAllocationId = $server->getOriginal('allocation_id');
            if ($previousAllocationId) {
                Allocation::whereKey($previousAllocationId)
                    ->where('server_id', $server->id)
                    ->update(['server_id' => null]);
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    public function egg(): BelongsTo
    {
        return $this->belongsTo(Egg::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(ServerVariable::class);
    }

    /**
     * Body for POST /api/servers/{uuid} on the owning node's agent - see
     * agent/internal/router/servers.go:createServerRequest for the
     * matching shape on the receiving end.
     *
     * @return array<string, mixed>
     */
    public function toAgentCreatePayload(): array
    {
        $this->loadMissing(['egg', 'allocation', 'variables.eggVariable']);

        $environment = [];
        foreach ($this->variables as $variable) {
            $environment[$variable->eggVariable->env_variable] = $variable->value
                ?? $variable->eggVariable->default_value;
        }

        return [
            'image' => $this->egg->docker_image,
            'startup' => $this->startup,
            'stop_command' => $this->egg->stop_command,
            'environment' => $environment,
            'limits' => [
                'memory_mb' => $this->memory,
                'swap_mb' => $this->swap,
                'disk_mb' => $this->disk,
                'cpu_limit' => $this->cpu,
            ],
            'allocations' => [
                ['ip' => $this->allocation->ip, 'port' => $this->allocation->port],
            ],
            'install' => $this->egg->install_script === null ? null : [
                'image' => $this->egg->install_image,
                'entrypoint' => $this->egg->install_entrypoint,
                'script' => $this->egg->install_script,
            ],
            'start_on_completion' => true,
        ];
    }
}
