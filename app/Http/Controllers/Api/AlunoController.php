<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Aluno\StoreAlunoRequest;
use App\Http\Requests\Aluno\UpdateAlunoRequest;
use App\Http\Resources\AlunoResource;
use App\Models\Aluno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AlunoController extends Controller
{
    public function index(Request $request)
    {
        $alunos = Aluno::query()
            ->with('plano')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($query) use ($request) {
                    $termo = $request->string('search');
                    $query->where('nome', 'ilike', "%{$termo}%")
                        ->orWhere('telefone', 'like', "%{$termo}%");
                })
            )
            ->when(
                $request->filled('ativo'),
                fn ($q) => $q->where('ativo', $request->boolean('ativo'))
            )
            ->when(
                $request->filled('plano_id'),
                fn ($q) => $q->where('plano_id', $request->integer('plano_id'))
            )
            ->orderBy('nome')
            ->paginate($request->integer('per_page', 20));

        return AlunoResource::collection($alunos);
    }

    public function store(StoreAlunoRequest $request): AlunoResource
    {
        $aluno = Aluno::create($request->validated());
        $aluno->load('plano');

        return new AlunoResource($aluno);
    }

    public function show(Aluno $aluno): AlunoResource
    {
        $aluno->load('plano');

        return new AlunoResource($aluno);
    }

    public function update(UpdateAlunoRequest $request, Aluno $aluno): AlunoResource
    {
        $aluno->update($request->validated());
        $aluno->load('plano');

        return new AlunoResource($aluno);
    }

    public function destroy(Aluno $aluno): Response|JsonResponse
    {
        if ($aluno->mensalidades()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir um aluno com mensalidades registradas. Marque como inativo.',
            ], 409);
        }

        $aluno->delete();

        return response()->noContent();
    }
}