<?php

namespace App\Services\Agent;

use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Panel -> agent HTTP calls for a single node. Mirrors the routes
 * registered in agent/internal/router/router.go. Never throws on a
 * non-2xx/network failure - callers inspect the Response (a node being
 * unreachable is an expected, recoverable condition, not a bug).
 */
class AgentClient
{
    // Comfortably above the agent's 30s graceful-stop timeout so a "stop"
    // power action isn't cut off by our own client before the agent
    // replies.
    private const TIMEOUT_SECONDS = 40;

    public function __construct(private readonly Node $node) {}

    public function createServer(Server $server): Response
    {
        return $this->request()
            ->post("/api/servers/{$server->uuid}", $server->toAgentCreatePayload());
    }

    public function power(Server $server, string $action): Response
    {
        return $this->request()
            ->post("/api/servers/{$server->uuid}/power", ['action' => $action]);
    }

    public function deleteServer(Server $server, bool $purge = false): Response
    {
        return $this->request()
            ->delete("/api/servers/{$server->uuid}", $purge ? ['purge' => 'true'] : []);
    }

    public function status(Server $server): Response
    {
        return $this->request()->get("/api/servers/{$server->uuid}");
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->node->baseUri())
            ->withToken($this->node->daemonAuthorizationToken())
            ->timeout(self::TIMEOUT_SECONDS)
            ->acceptJson();
    }
}
