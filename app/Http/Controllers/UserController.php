<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $currentUserId = $request->user()?->id;

        $users = User::query()
            ->with(['corporation'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (User $user) use ($currentUserId): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'corporation_id' => $user->corporation_id,
                    'corporation_name' => $user->corporation?->name ?? null,
                    'created_at' => $user->created_at?->toISOString(),
                    'is_current_user' => $currentUserId !== null && $user->id === $currentUserId,
                ];
            })
            ->values();

        return response()->json($users);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->input('id')),
            ],
        ]);

        $user = app()->make(UserService::class)->store($validated);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ], 201);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $result = app()->make(UserService::class)->deleteById($id, $request->user()?->id);

        if ($result === UserService::DELETE_NOT_FOUND) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($result === UserService::DELETE_CURRENT_USER_FORBIDDEN) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 409);
        }

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }

    /**
     * Update a user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'corporation_id' => ['sometimes', 'nullable', 'exists:corporations,id'],
        ]);

        $user->update($validated);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'corporation_id' => $user->corporation_id,
            'corporation_name' => $user->corporation?->name ?? null,
            'message' => 'User updated.',
        ]);
    }

    /**
     * Get corporations list for dropdown
     */
    public function corporations(Request $request): JsonResponse
    {
        $corporations = \App\Models\Corporation::orderBy('name')->get([
            'id',
            'uuid',
            'name',
        ]);

        return response()->json($corporations);
    }
}
