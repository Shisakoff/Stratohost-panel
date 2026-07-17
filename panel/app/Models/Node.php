<?php

namespace App\Models;

use App\Services\Agent\AgentClient;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'description', 'fqdn', 'scheme', 'daemon_port',
    'memory', 'memory_overallocate', 'disk', 'disk_overallocate',
    'upload_size', 'maintenance_mode',
])]
// The encrypted cast on daemon_token decrypts back to plaintext on every
// read, including when a Node is serialized to JSON - without this it
// would leak the live secret to any authenticated admin via GET
// /api/nodes, defeating the "shown once at creation" design entirely.
#[Hidden(['daemon_token'])]
class Node extends Model
{
    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
            // Reversible encryption (APP_KEY), not a one-way hash: the panel
            // must be able to decrypt this to authenticate outgoing requests
            // to the agent, it only needs to be unreadable from a raw DB dump.
            'daemon_token' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Node $node) {
            $node->uuid ??= (string) Str::uuid();
        });
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Generate a fresh public token id + secret pair for a new node. The
     * secret is only ever available in plaintext at creation time - the
     * caller is responsible for displaying it to the admin once so it can
     * be copied into the agent's config or install command.
     *
     * @return array{id: string, token: string}
     */
    public static function generateDaemonToken(): array
    {
        return [
            'id' => Str::random(16),
            'token' => Str::random(40),
        ];
    }

    /**
     * The value the panel sends as `Authorization: Bearer <this>` when
     * calling the agent's API.
     */
    public function daemonAuthorizationToken(): string
    {
        return "{$this->daemon_token_id}.{$this->daemon_token}";
    }

    public function baseUri(): string
    {
        return "{$this->scheme}://{$this->fqdn}:{$this->daemon_port}";
    }

    public function agent(): AgentClient
    {
        return new AgentClient($this);
    }

    /**
     * The command an admin runs on the node (as root) to install and
     * register the agent, using a one-time token pair only ever available
     * in plaintext right after generateDaemonToken().
     */
    public function installCommand(string $tokenId, string $token): string
    {
        $repoUrl = config('stratohost.repo_url');
        $panelUrl = rtrim(config('app.url'), '/');

        return sprintf(
            'git clone --depth 1 %s stratohost && cd stratohost/installer && ./agent-install.sh --panel-url=%s --node-uuid=%s --token-id=%s --token=%s --port=%d',
            $repoUrl,
            $panelUrl,
            $this->uuid,
            $tokenId,
            $token,
            $this->daemon_port
        );
    }
}
