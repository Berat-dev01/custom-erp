<?php

namespace App\Erp\Services\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class ErpAuthorization
{
    public function __construct(private readonly ErpPermissionCatalog $catalog) {}

    public function can(?Authenticatable $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->enabled()) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole($this->catalog->roleName('erp_admin'))) {
            return true;
        }

        if (! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission, $this->catalog->guardName());
        } catch (GuardDoesNotMatch|PermissionDoesNotExist) {
            return false;
        }
    }

    public function enabled(): bool
    {
        return (bool) config('erp.permissions.enabled', true);
    }
}
