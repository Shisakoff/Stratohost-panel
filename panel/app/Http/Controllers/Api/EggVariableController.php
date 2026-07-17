<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Egg;
use App\Models\EggVariable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EggVariableController extends Controller
{
    public function index(Egg $egg): JsonResponse
    {
        return response()->json($egg->variables()->orderBy('name')->get());
    }

    public function store(Request $request, Egg $egg): JsonResponse
    {
        $variable = $egg->variables()->create($this->validated($request));

        return response()->json($variable, 201);
    }

    public function show(EggVariable $variable): JsonResponse
    {
        return response()->json($variable);
    }

    public function update(Request $request, EggVariable $variable): JsonResponse
    {
        $variable->update($this->validated($request, $variable));

        return response()->json($variable);
    }

    public function destroy(EggVariable $variable): JsonResponse
    {
        $variable->delete();

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?EggVariable $variable = null): array
    {
        $sometimes = $variable ? 'sometimes|' : '';

        return $request->validate([
            'name' => "{$sometimes}required|string|max:191",
            'env_variable' => "{$sometimes}required|string|max:191|regex:/^[A-Z0-9_]+$/",
            'description' => 'nullable|string',
            'default_value' => 'nullable|string',
            'rules' => 'nullable|string|max:191',
            'user_viewable' => 'nullable|boolean',
            'user_editable' => 'nullable|boolean',
        ]);
    }
}
