<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DatabaseHost;
use App\Services\Database\DatabaseProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DatabaseHostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DatabaseHost::withCount('serverDatabases')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'host' => 'required|string|max:191',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'required|string|max:191',
            'password' => 'required|string',
            'max_databases' => 'nullable|integer|min:1',
        ]);

        $host = new DatabaseHost($data);
        $host->save();

        try {
            (new DatabaseProvisioner($host))->ping();
        } catch (Throwable $e) {
            $host->delete();
            abort(422, 'Could not connect to that database host: '.$e->getMessage());
        }

        return response()->json($host, 201);
    }

    public function show(DatabaseHost $host): JsonResponse
    {
        return response()->json($host->loadCount('serverDatabases'));
    }

    public function update(Request $request, DatabaseHost $host): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191',
            'host' => 'sometimes|required|string|max:191',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'sometimes|required|string|max:191',
            'password' => 'nullable|string',
            'max_databases' => 'nullable|integer|min:1',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $host->update($data);

        return response()->json($host);
    }

    public function destroy(DatabaseHost $host): JsonResponse
    {
        if ($host->serverDatabases()->exists()) {
            abort(409, 'This host still has databases provisioned on it - delete those first.');
        }

        $host->delete();

        return response()->json(null, 204);
    }
}
