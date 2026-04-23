<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plano\StorePlanoRequest;
use App\Http\Requests\Plano\UpdatePlanoRequest;
use App\Http\Resources\PlanoResource;
use App\Models\Plano;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PlanoController extends Controller
{
    public function index()
    {
        $planos = Plano::orderBy('valor')->get();

        return PlanoResource::collection($planos);
    }

    public function store(StorePlanoRequest $request): PlanoResource
    {
        $plano = Plano::create($request->validated());

        return new PlanoResource($plano);
    }

    public function show(Plano $plano): PlanoResource
    {
        return new PlanoResource($plano);
    }

    public function update(UpdatePlanoRequest $request, Plano $plano): PlanoResource
    {
        $plano->update($request->validated());

        return new PlanoResource($plano);
    }

    public function destroy(Plano $plano): Response|JsonResponse
    {
        if ($plano->alunos()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir um plano com alunos vinculados.',
            ], 409);
        }

        $plano->delete();

        return response()->noContent();
    }
}