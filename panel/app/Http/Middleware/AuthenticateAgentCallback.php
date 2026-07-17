<?php

namespace App\Http\Middleware;

use App\Models\Node;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates callbacks FROM an agent (e.g. install script results) using
 * the same "Authorization: Bearer <token_id>.<token>" daemon token the
 * panel sends TO the agent - see agent/internal/panel/client.go. It's a
 * shared secret between the panel and that one node, valid in both
 * directions.
 */
class AuthenticateAgentCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            abort(401, 'Missing or invalid Authorization header.');
        }

        $credential = substr($header, 7);
        [$tokenId, $token] = array_pad(explode('.', $credential, 2), 2, '');

        $node = Node::where('daemon_token_id', $tokenId)->first();
        if (! $node || ! hash_equals($node->daemon_token, $token)) {
            abort(401, 'Invalid daemon token.');
        }

        $request->attributes->set('node', $node);

        return $next($request);
    }
}
