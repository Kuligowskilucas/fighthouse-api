<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\AlunoResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\User\UpdateUserRequest;

class UserController extends Controller
{
    /**
     * Lista todos os usuários (admin only — garantido pela rota).
     */
    public function index(): AnonymousResourceCollection
    {
        $users = User::with('aluno:id,nome')->orderBy('name')->get();

        return UserResource::collection($users);
    }

    /**
     * Cria um novo usuário (admin only — garantido pela rota).
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->input('role', 'professor'), // default professor ao criar via API
            'aluno_id' => $request->aluno_id,
        ]);

        return new UserResource($user);
    }

    /**
     * Retorna o aluno vinculado ao usuário logado (role aluno — garantido pela rota).
     */
    public function meuAluno(Request $request): AlunoResource|JsonResponse
    {
        $aluno = $request->user()->aluno;

        if (! $aluno) {
            return response()->json([
                'message' => 'Nenhum aluno vinculado a este usuário.',
            ], 404);
        }

        $aluno->load([
            'plano',
            'mensalidades' => fn ($q) => $q->orderBy('mes_referencia', 'desc'),
        ]);

        return new AlunoResource($aluno);
    }

    /**
     * Troca a própria senha (qualquer role autenticado).
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $user->currentAccessToken()->id;

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        return response()->json([
            'message' => 'Senha alterada com sucesso. Outros dispositivos foram desconectados.',
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Você não pode excluir sua própria conta.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Usuário excluído com sucesso.']);
    }
}