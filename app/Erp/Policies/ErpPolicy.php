<?php

namespace App\Erp\Policies;

use App\Erp\Services\Authorization\ErpAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class ErpPolicy
{
    protected function can(Authenticatable $user, string $permission): bool
    {
        return app(ErpAuthorization::class)->can($user, $permission);
    }
}
