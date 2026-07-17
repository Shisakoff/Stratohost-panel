<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Server::with(['node:id,name', 'egg:id,name', 'allocation:id,ip,port', 'owner:id,name'])
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'node_id' => 'required|exists:nodes,id',
            'egg_id' => 'required|exists:eggs,id',
            'allocation_id' => 'required|exists:allocations,id',
            'owner_id' => 'nullable|exists:users,id',
            'startup' => 'nullable|string',
            'memory' => 'required|integer|min:0',
            'swap' => 'nullable|integer|min:0',
            'disk' => 'required|integer|min:0',
            'cpu' => 'nullable|integer|min:0',
            'variables' => 'nullable|array',
            'variables.*.egg_variable_id' => 'required_with:variables|integer',
            'variables.*.value' => 'nullable|string',
        ]);

        $egg = Egg::with('variables')->findOrFail($data['egg_id']);

        $allocation = Allocation::findOrFail($data['allocation_id']);
        if ($allocation->node_id !== (int) $data['node_id']) {
            abort(422, 'That allocation does not belong to the selected node.');
        }
        if ($allocation->server_id !== null) {
            abort(409, 'That allocation is already in use by another server.');
        }

        $submittedVariables = collect($data['variables'] ?? [])->keyBy('egg_variable_id');
        $variableValues = $egg->variables->mapWithKeys(function ($eggVariable) use ($submittedVariables) {
            $value = $submittedVariables->get($eggVariable->id)['value'] ?? $eggVariable->default_value;

            Validator::make(['value' => $value], ['value' => $eggVariable->rules])->validate();

            return [$eggVariable->id => $value];
        });

        $server = DB::transaction(function () use ($request, $data, $egg, $variableValues) {
            $server = Server::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'owner_id' => $data['owner_id'] ?? $request->user()->id,
                'node_id' => $data['node_id'],
                'allocation_id' => $data['allocation_id'],
                'egg_id' => $egg->id,
                'startup' => $data['startup'] ?? $egg->startup,
                'memory' => $data['memory'],
                'swap' => $data['swap'] ?? 0,
                'disk' => $data['disk'],
                'cpu' => $data['cpu'] ?? 100,
                'status' => 'installing',
            ]);

            foreach ($variableValues as $eggVariableId => $value) {
                $server->variables()->create([
                    'egg_variable_id' => $eggVariableId,
                    'value' => $value,
                ]);
            }

            return $server;
        });

        $this->provision($server);

        return response()->json($server->load(['node', 'egg', 'allocation', 'variables.eggVariable']), 201);
    }

    public function show(Server $server): JsonResponse
    {
        return response()->json($server->load(['node', 'egg', 'allocation', 'owner', 'variables.eggVariable']));
    }

    /**
     * Phase 1 keeps this to cosmetic fields only: changing memory/cpu/disk
     * or moving node/egg/allocation would also need to update (or
     * recreate) the running container, which isn't wired up yet.
     */
    public function update(Request $request, Server $server): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191',
            'description' => 'nullable|string',
        ]);

        $server->update($data);

        return response()->json($server);
    }

    public function destroy(Server $server): JsonResponse
    {
        try {
            $server->node->agent()->deleteServer($server, purge: true);
        } catch (ConnectionException $e) {
            Log::warning('Could not reach node to delete server container, removing panel record anyway.', [
                'server_id' => $server->id,
                'node_id' => $server->node_id,
                'error' => $e->getMessage(),
            ]);
        }

        $server->delete();

        return response()->json(null, 204);
    }

    public function power(Request $request, Server $server): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:start,stop,restart,kill',
        ]);

        $response = $server->node->agent()->power($server, $data['action']);
        if (! $response->successful()) {
            abort(502, 'The node did not accept that power action: '.$response->body());
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Live container state from the node's agent - servers.status only
     * tracks the install lifecycle, not moment-to-moment running/stopped.
     */
    public function status(Server $server): JsonResponse
    {
        $response = $server->node->agent()->status($server);
        if (! $response->successful()) {
            abort(502, 'Could not reach the node to check server status.');
        }

        return response()->json($response->json());
    }

    private function provision(Server $server): void
    {
        try {
            $response = $server->node->agent()->createServer($server);
            if (! $response->successful()) {
                Log::error('Agent rejected server creation.', [
                    'server_id' => $server->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $server->update(['status' => 'install_failed']);
            }
        } catch (ConnectionException $e) {
            Log::error('Could not reach node to provision server.', [
                'server_id' => $server->id,
                'node_id' => $server->node_id,
                'error' => $e->getMessage(),
            ]);
            $server->update(['status' => 'install_failed']);
        }
    }
}
