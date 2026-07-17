<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllocationController extends Controller
{
    public function index(Node $node): JsonResponse
    {
        return response()->json(
            $node->allocations()->orderBy('ip')->orderBy('port')->get()
        );
    }

    /**
     * Bulk-creates allocations for a single IP across a list of ports, so
     * an admin can open e.g. 25565-25570 in one request instead of five.
     */
    public function store(Request $request, Node $node): JsonResponse
    {
        $data = $request->validate([
            'ip' => 'required|ip',
            'ip_alias' => 'nullable|string|max:191',
            'ports' => 'required|array|min:1|max:1000',
            'ports.*' => 'integer|min:1|max:65535',
        ]);

        $created = collect($data['ports'])
            ->unique()
            ->map(fn (int $port) => Allocation::firstOrCreate([
                'node_id' => $node->id,
                'ip' => $data['ip'],
                'port' => $port,
            ], [
                'ip_alias' => $data['ip_alias'] ?? null,
            ]))
            ->values();

        return response()->json($created, 201);
    }

    public function destroy(Allocation $allocation): JsonResponse
    {
        if ($allocation->server_id !== null) {
            abort(409, 'This allocation is in use by a server - delete the server first.');
        }

        $allocation->delete();

        return response()->json(null, 204);
    }
}
