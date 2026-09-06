<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** GET /api/users (admin only) */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->when($request->query('role'), fn ($q, $role) => $q->whereHas('role', fn ($r) => $r->where('name', $role)))
            ->when($request->query('active') !== null, fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return UserResource::collection($users);
    }

    /** POST /api/users (admin only) */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => Role::where('name', $data['role'])->value('id'),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return (new UserResource($user->load('role')))->response()->setStatusCode(201);
    }

    /** GET /api/users/{user} (admin only) */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('role'));
    }

    /** PUT /api/users/{user} (admin only) */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => Role::where('name', $data['role'])->value('id'),
        ]);

        return new UserResource($user->load('role'));
    }
}
