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
        $user = $request->user()->load('aluno.plano');

        if (! $user->aluno) {
            return response()->json([
                'message' => 'Nenhum aluno vinculado a este usuário.',
            ], 404);
        }

        return new AlunoResource($user->aluno);
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
}