<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Universe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UniverseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Universe::query()
            ->withCount('stories')
            ->withCount('characters');

        if ($request->has('official')) {
            $query->where('is_official', $request->boolean('official'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        $universes = $query->orderBy('name')->get();

        return response()->json($universes);
    }

    public function show(Universe $universe): JsonResponse
    {
        $universe->load('creator:id,username,avatar_url');
        $universe->loadCount(['stories', 'characters']);

        return response()->json($universe);
    }
}
