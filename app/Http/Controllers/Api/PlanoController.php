<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plano\StorePlanoRequest;
use App\Http\Requests\Plano\UpdatePlanoRequest;
use App\Http\Resources\PlanoResource;
use App\Models\Plano;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PlanoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $planos = Plano::with('horarios')->withCount('alunos')->get();
        return PlanoResource::collection($planos);
    }

    public function store(StorePlanoRequest $request): PlanoResource
    {
        $plano = Plano::create($request->safe()->except('horarios'));
        $this->syncHorarios($plano, $request->input('horarios', []));
        return new PlanoResource($plano->load('horarios'));
    }

    public function show(Plano $plano): PlanoResource
    {
        return new PlanoResource($plano->load('horarios')->loadCount('alunos'));
    }

    public function update(UpdatePlanoRequest $request, Plano $plano): PlanoResource
    {
        $plano->update($request->safe()->except('horarios'));
        $this->syncHorarios($plano, $request->input('horarios', []));
        return new PlanoResource($plano->load('horarios'));
    }

    public function destroy(Plano $plano): Response
    {
        if ($plano->alunos()->exists()) {
            abort(422, 'Não é possível excluir uma turma com alunos vinculados.');
        }

        $plano->delete();
        return response()->noContent();
    }

    private function syncHorarios(Plano $plano, array $horarios): void
    {
        $plano->horarios()->delete();

        if (!empty($horarios)) {
            $plano->horarios()->createMany(
                array_map(
                    fn(string $h) => ['horario' => $h],
                    array_unique($horarios)
                )
            );
        }
    }
}