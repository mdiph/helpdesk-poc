<?php

namespace App\Http\Controllers\Web;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ResetPasswordRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('role')
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'ilike', "%{$term}%")
                ->orWhere('email', 'ilike', "%{$term}%")))
            ->when($request->query('role'), fn ($q, $role) => $q->whereHas('role', fn ($r) => $r->where('name', $role)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => RoleName::options(),
            'filters' => $request->query(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['roles' => RoleName::options()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => Role::where('name', $data['role'])->value('id'),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user,
            'roles' => RoleName::options(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => Role::where('name', $data['role'])->value('id'),
        ]);

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    /** Enable / disable an account. */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()
            ->route('users.index')
            ->with('status', $user->is_active ? 'User enabled.' : 'User disabled.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make($request->validated('password'))]);

        // Force re-authentication everywhere for that user.
        $user->tokens()->delete();

        return redirect()
            ->route('users.index')
            ->with('status', "Password reset for {$user->name}.");
    }
}
