<?php

namespace MeuCandidato\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use MeuCandidato\Candidate\Models\Politician;
use MeuCandidato\Identity\Models\Follow;

class FollowController extends Controller
{
    public function store(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $request->validate(['id' => ['required', Rule::uuid()]]);

        $politician = Politician::find($id);

        if (! $politician) {
            return response()->json(['error' => 'Político não encontrado'], 404);
        }

        Follow::firstOrCreate([
            'user_id' => $user->id,
            'politician_id' => $politician->id,
        ]);

        return response()->json(['following' => true, 'message' => 'Agora você segue este político']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Não autenticado'], 401);
        }

        $request->validate(['id' => ['required', Rule::uuid()]]);

        $politician = Politician::find($id);

        if (! $politician) {
            return response()->json(['error' => 'Político não encontrado'], 404);
        }

        Follow::where('user_id', $user->id)
            ->where('politician_id', $politician->id)
            ->delete();

        return response()->json(['following' => false, 'message' => 'Deixou de seguir este político']);
    }
}
