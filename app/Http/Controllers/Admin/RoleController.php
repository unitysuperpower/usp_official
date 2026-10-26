<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\ReactPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return ReactPage::render('admin.roles.index', [
            'roles' => Role::withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
            'groups' => config('permissions.groups'),
            'defaultRole' => config('permissions.default_role'),
        ]);
    }

    public function save(Request $request, ?Role $role = null)
    {
        abort_if($role?->is_system, 403, 'The Super Admin role always has full access and cannot be changed.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role?->id)],
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(Role::catalog())],
        ]);
        $role ??= new Role(['slug' => $this->slug($data['name'])]);
        $role->fill(['name' => $data['name'], 'description' => $data['description'] ?? null, 'permissions' => array_values(array_unique($data['permissions'] ?? []))])->save();
        $this->resync($role);

        return back()->with('success', "Role “{$role->name}” saved.");
    }

    public function destroy(Role $role)
    {
        abort_if($role->is_system || $role->slug === config('permissions.default_role'), 403, 'This role is required and cannot be deleted.');
        $users = $role->users()->get();
        $role->delete();
        $users->each(fn ($user) => $user->syncRoles($user->roles()->pluck('roles.id')->all() ?: [Role::where('slug', config('permissions.default_role'))->value('id')]));

        return back()->with('success', "Role “{$role->name}” deleted. Its members kept their other roles.");
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        for ($i = 2; Role::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    // Permissions changed, so members may have gained or lost admin-area access.
    private function resync(Role $role): void
    {
        $role->users()->with('roles')->get()->each(fn ($user) => $user->forceFill(['is_admin' => $user->permissions() !== []])->save());
    }
}
