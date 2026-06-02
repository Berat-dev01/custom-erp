<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Services\Authorization\ErpPermissionCatalog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    private array $systemRoles = ['erp_admin', 'erp_hr', 'erp_finance', 'erp_inventory', 'erp_sales', 'erp_viewer'];

    public function __construct(private ErpPermissionCatalog $catalog) {}

    public function index()
    {
        Gate::authorize('erp.users.manage');

        $roles = Role::where('guard_name', $this->catalog->guardName())
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->get();

        return view('erp::admin.roles.index', compact('roles'));
    }

    public function create()
    {
        Gate::authorize('erp.users.manage');

        $permissions = $this->groupedPermissions();

        return view('erp::admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.users.manage');

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:60', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => $this->catalog->guardName()]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('erp.roles.index')->with('success', __('Rol oluşturuldu.'));
    }

    public function edit(Role $role)
    {
        Gate::authorize('erp.users.manage');

        $permissions     = $this->groupedPermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('erp::admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        Gate::authorize('erp.users.manage');

        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('erp.roles.index')->with('success', __('Rol güncellendi.'));
    }

    public function destroy(Role $role)
    {
        Gate::authorize('erp.users.manage');

        abort_if(in_array($role->name, $this->systemRoles), 403, __('Sistem rolleri silinemez.'));

        $role->delete();

        return redirect()->route('erp.roles.index')->with('success', __('Rol silindi.'));
    }

    public function users()
    {
        Gate::authorize('erp.users.manage');

        $guard = $this->catalog->guardName();
        $users = User::with('roles')->orderBy('name')->paginate(30);
        $roles = Role::where('guard_name', $guard)->orderBy('name')->get();

        return view('erp::admin.roles.users', compact('users', 'roles'));
    }

    public function assignRole(Request $request, User $user)
    {
        Gate::authorize('erp.users.manage');

        $data = $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user->assignRole($data['role']);

        return back()->with('success', __('Rol atandı.'));
    }

    public function removeRole(Request $request, User $user)
    {
        Gate::authorize('erp.users.manage');

        $data = $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user->removeRole($data['role']);

        return back()->with('success', __('Rol kaldırıldı.'));
    }

    private function groupedPermissions(): array
    {
        $all = Permission::where('guard_name', $this->catalog->guardName())->orderBy('name')->get();

        $groups = [];
        foreach ($all as $perm) {
            $parts  = explode('.', $perm->name);
            $module = $parts[1] ?? 'other';
            $groups[$module][] = $perm;
        }

        return $groups;
    }
}
