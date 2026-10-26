<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'role' => 'nullable|string|max:60']);
        $users = User::with('roles:id,slug,name')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['role'] ?? null, fn ($q, $slug) => $q->whereHas('roles', fn ($r) => $r->where('slug', $slug)))
            ->latest('id')->paginate(20)->withQueryString();
        $users->getCollection()->transform(fn (User $user) => $user->only(['id', 'name', 'email', 'is_admin', 'created_at']) + [
            'role_ids' => $user->roles->pluck('id'), 'roles' => $user->roles->pluck('name'),
        ]);

        return ReactPage::render('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderByDesc('is_system')->orderBy('name')->get(['id', 'slug', 'name', 'is_system']),
            'filters' => $filters,
            'canAssignSuper' => $request->user()->isSuperAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'roles' => 'required|array|min:1',
            'roles.*' => 'integer|exists:roles,id',
        ]);
        $this->guardAssignment($request, [], $data['roles']);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles($data['roles']);

        return back()->with('success', "Account created for {$user->email}.");
    }

    public function roles(Request $request, User $user)
    {
        $data = $request->validate(['roles' => 'required|array|min:1', 'roles.*' => 'integer|exists:roles,id'], ['roles.required' => 'Give the account at least one role.']);
        $roles = array_map('intval', $data['roles']);
        $this->guardAssignment($request, $user->roles()->pluck('roles.id')->all(), $roles);
        $super = Role::where('is_system', true)->value('id');
        if (! in_array($super, $roles, true) && $user->isSuperAdmin()) {
            throw_if($user->is($request->user()), ValidationException::withMessages(['roles' => 'You cannot remove your own Super Admin role.']));
            throw_if(Role::find($super)->users()->count() <= 1, ValidationException::withMessages(['roles' => 'At least one Super Admin must remain.']));
        }
        $user->syncRoles($roles);

        return back()->with('success', "Roles updated for {$user->email}.");
    }

    // Only a Super Admin may grant or take away the Super Admin role, and nobody else may
    // hand out a role carrying permissions they do not hold themselves.
    private function guardAssignment(Request $request, array $before, array $after): void
    {
        $actor = $request->user();
        if ($actor->isSuperAdmin()) {
            return;
        }
        $before = array_map('intval', $before);
        $after = array_map('intval', $after);
        $super = Role::where('is_system', true)->value('id');
        throw_if(in_array($super, $before, true) !== in_array($super, $after, true), ValidationException::withMessages(['roles' => 'Only a Super Admin can grant or remove the Super Admin role.']));
        $added = Role::whereIn('id', array_diff($after, $before))->get();
        $missing = $added->flatMap(fn (Role $role) => $role->permissions ?? [])->diff($actor->permissions());
        throw_if($missing->isNotEmpty(), ValidationException::withMessages(['roles' => 'You can only assign roles whose permissions you also have.']));
    }
}
