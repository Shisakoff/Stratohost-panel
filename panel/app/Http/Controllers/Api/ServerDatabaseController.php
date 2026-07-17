<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DatabaseHost;
use App\Models\Server;
use App\Models\ServerDatabase;
use App\Services\Database\DatabaseProvisioner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerDatabaseController extends Controller
{
    public function index(Server $server): JsonResponse
    {
        return response()->json(
            $server->databases()->with('databaseHost:id,name,host,port')->get()
        );
    }

    public function store(Request $request, Server $server): JsonResponse
    {
        $data = $request->validate([
            'database_host_id' => 'required|exists:database_hosts,id',
        ]);

        $host = DatabaseHost::findOrFail($data['database_host_id']);

        if ($host->max_databases !== null && $host->serverDatabases()->count() >= $host->max_databases) {
            abort(409, 'This database host is at capacity.');
        }

        // MySQL usernames cap at 32 chars - keep well under that
        // regardless of how large server ids or the random suffix get.
        $suffix = Str::lower(Str::random(8));
        $databaseName = "s{$server->id}_{$suffix}";
        $username = "u{$server->id}_{$suffix}";
        $password = Str::password(24);

        (new DatabaseProvisioner($host))->create($databaseName, $username, $password);

        $database = $server->databases()->create([
            'database_host_id' => $host->id,
            'database' => $databaseName,
            'username' => $username,
            'password' => $password,
            'remote' => '%',
        ]);

        return response()->json($database->load('databaseHost:id,name,host,port'), 201);
    }

    public function destroy(ServerDatabase $database): JsonResponse
    {
        (new DatabaseProvisioner($database->databaseHost))
            ->drop($database->database, $database->username, $database->remote);

        $database->delete();

        return response()->json(null, 204);
    }
}
