<?php

namespace App\Erp\Services\Authorization;

class ErpPermissionCatalog
{
    public function guardName(): string
    {
        return (string) config('erp.permissions.guard', 'web');
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values(array_unique(config('erp.permissions.permissions', [])));
    }

    /**
     * @return array<string, array{name: string, permissions: list<string>}>
     */
    public function roles(): array
    {
        return config('erp.permissions.roles', []);
    }

    public function roleName(string $roleKey): string
    {
        return $this->roles()[$roleKey]['name'] ?? $roleKey;
    }

    /**
     * @return list<string>
     */
    public function permissionsForRole(string $roleKey): array
    {
        $permissions = $this->roles()[$roleKey]['permissions'] ?? [];

        if (in_array('*', $permissions, true)) {
            return $this->permissions();
        }

        return array_values(array_intersect($permissions, $this->permissions()));
    }
}
