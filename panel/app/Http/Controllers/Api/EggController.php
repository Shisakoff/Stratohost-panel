<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Egg;
use App\Models\Nest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EggController extends Controller
{
    public function index(Nest $nest): JsonResponse
    {
        return response()->json($nest->eggs()->withCount('variables')->orderBy('name')->get());
    }

    public function store(Request $request, Nest $nest): JsonResponse
    {
        $data = $this->validated($request);

        $egg = $nest->eggs()->create($data);

        return response()->json($egg, 201);
    }

    public function show(Egg $egg): JsonResponse
    {
        return response()->json($egg->load('variables'));
    }

    public function update(Request $request, Egg $egg): JsonResponse
    {
        $egg->update($this->validated($request, $egg));

        return response()->json($egg);
    }

    public function destroy(Egg $egg): JsonResponse
    {
        if ($egg->servers()->exists()) {
            abort(409, 'This egg is used by at least one server - delete those first.');
        }

        $egg->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Egg $egg = null): array
    {
        $sometimes = $egg ? 'sometimes|' : '';

        return $request->validate([
            'name' => "{$sometimes}required|string|max:191",
            'description' => 'nullable|string',
            'docker_image' => "{$sometimes}required|string|max:191",
            'startup' => "{$sometimes}required|string",
            'stop_command' => 'nullable|string|max:191',
            'install_image' => 'nullable|string|max:191',
            'install_entrypoint' => 'nullable|string|max:191',
            'install_script' => 'nullable|string',
        ]);
    }
}
