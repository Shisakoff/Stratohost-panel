<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Nest::withCount('eggs')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:191|unique:nests,name',
            'description' => 'nullable|string',
        ]);

        return response()->json(Nest::create($data), 201);
    }

    public function show(Nest $nest): JsonResponse
    {
        return response()->json($nest->load('eggs'));
    }

    public function update(Request $request, Nest $nest): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:191|unique:nests,name,'.$nest->id,
            'description' => 'nullable|string',
        ]);

        $nest->update($data);

        return response()->json($nest);
    }

    public function destroy(Nest $nest): JsonResponse
    {
        if ($nest->eggs()->exists()) {
            abort(409, 'This nest still has eggs in it - delete them first.');
        }

        $nest->delete();

        return response()->json(null, 204);
    }
}
