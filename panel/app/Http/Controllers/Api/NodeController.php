<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Node::withCount(['allocations', 'servers'])->orderBy('name')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:nodes,name',
            'description' => 'nullable|string',
            'fqdn' => 'required|string|max:191',
            'scheme' => 'required|in:http,https',
            'daemon_port' => 'required|integer|min:1|max:65535',
            'memory' => 'required|integer|min:0',
            'memory_overallocate' => 'nullable|integer|min:0',
            'disk' => 'required|integer|min:0',
            'disk_overallocate' => 'nullable|integer|min:0',
            'upload_size' => 'nullable|integer|min:1',
        ]);

        $node = new Node($data);
        $tokenPair = Node::generateDaemonToken();
        $node->daemon_token_id = $tokenPair['id'];
        $node->daemon_token = $tokenPair['token'];
        $node->save();

        return response()->json([
            'node' => $node,
            // Only ever returned here, right after creation - the panel
            // stores daemon_token encrypted and never exposes it again.
            'daemon_token' => $tokenPair,
            'install_command' => $node->installCommand($tokenPair['id'], $tokenPair['token']),
        ], 201);
    }

    public function show(Node $node): JsonResponse
    {
        return response()->json($node->loadCount(['allocations', 'servers']));
    }

    public function update(Request $request, Node $node): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191|unique:nodes,name,'.$node->id,
            'description' => 'nullable|string',
            'fqdn' => 'sometimes|required|string|max:191',
            'scheme' => 'sometimes|required|in:http,https',
            'daemon_port' => 'sometimes|required|integer|min:1|max:65535',
            'memory' => 'sometimes|required|integer|min:0',
            'memory_overallocate' => 'nullable|integer|min:0',
            'disk' => 'sometimes|required|integer|min:0',
            'disk_overallocate' => 'nullable|integer|min:0',
            'upload_size' => 'nullable|integer|min:1',
            'maintenance_mode' => 'nullable|boolean',
        ]);

        $node->update($data);

        return response()->json($node);
    }

    public function destroy(Node $node): JsonResponse
    {
        if ($node->servers()->exists()) {
            abort(409, 'This node still has servers on it - delete or move them first.');
        }

        $node->delete();

        return response()->json(null, 204);
    }
}
