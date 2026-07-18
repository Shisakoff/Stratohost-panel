<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\Egg;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller implements HasMiddleware
{
    /**
     * Creating/editing/deleting servers is admin-only; viewing, power
     * actions, and databases are open to any authenticated user but
     * scoped to their own servers via ServerPolicy::view.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('root_admin', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $query = Server::with(['node:id,name', 'egg:id,name', 'allocation:id,ip,port', 'owner:id,name'])
            ->orderBy('name');

        if (! $request->user()->root_admin) {
            $query->where('owner_id', $request->user()->id);
        }

        return response()->json($query->get());
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

    public function show(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

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
        $this->authorize('view', $server);

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
    public function status(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $response = $server->node->agent()->status($server);
        if (! $response->successful()) {
            abort(502, 'Could not reach the node to check server status.');
        }

        return response()->json($response->json());
    }

    /**
     * Live CPU/memory sample from the node's agent, for the "Mes serveurs"
     * dashboard and server detail graphs. Unlike status(), a failed/empty
     * read isn't fatal - the frontend just skips that tick of the graph.
     */
    public function stats(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $response = $server->node->agent()->stats($server);
        if (! $response->successful()) {
            abort(502, 'Could not reach the node to read server stats.');
        }

        return response()->json($response->json());
    }

    /**
     * Lets a server's owner (not just admins) tweak the egg's startup
     * variables - e.g. the FiveM license key or a hostname - without
     * needing root_admin. Values are validated against the same rules
     * the egg variable was defined with, and non-admins are blocked from
     * touching a variable the egg marked as not user_editable.
     */
    public function updateVariables(Request $request, Server $server): JsonResponse
    {
        $this->authorize('view', $server);

        $data = $request->validate([
            'variables' => 'required|array',
            'variables.*.egg_variable_id' => 'required|integer',
            'variables.*.value' => 'nullable|string',
        ]);

        $server->loadMissing('egg.variables');
        $eggVariables = $server->egg->variables->keyBy('id');
        $isAdmin = (bool) $request->user()->root_admin;

        foreach ($data['variables'] as $entry) {
            $eggVariable = $eggVariables->get($entry['egg_variable_id']);
            if (! $eggVariable) {
                abort(422, 'That variable does not belong to this server\'s egg.');
            }
            if (! $isAdmin && ! $eggVariable->user_editable) {
                abort(403, "You can't edit \"{$eggVariable->name}\".");
            }

            Validator::make(['value' => $entry['value']], ['value' => $eggVariable->rules])->validate();

            $server->variables()->updateOrCreate(
                ['egg_variable_id' => $eggVariable->id],
                ['value' => $entry['value']]
            );
        }

        return response()->json($server->load('variables.eggVariable'));
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
