<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the install-script result from an agent - see
 * agent/internal/panel/client.go:ReportInstall. Authenticated by
 * AuthenticateAgentCallback, not Sanctum: this is node-to-panel, not a
 * logged-in admin.
 */
class ServerInstallCallbackController extends Controller
{
    public function __invoke(Request $request, string $uuid): JsonResponse
    {
        $data = $request->validate([
            'successful' => 'required|boolean',
        ]);

        $server = Server::where('uuid', $uuid)->firstOrFail();

        /** @var Node $node */
        $node = $request->attributes->get('node');
        if ($server->node_id !== $node->id) {
            abort(403, 'That server does not belong to this node.');
        }

        $server->update(['status' => $data['successful'] ? 'offline' : 'install_failed']);

        return response()->json(null, 204);
    }
}
