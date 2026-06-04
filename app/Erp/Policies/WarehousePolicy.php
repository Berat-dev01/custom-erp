<?php

namespace App\Erp\Policies;

use App\Erp\Models\Warehouse;
use Illuminate\Contracts\Auth\Authenticatable;

class WarehousePolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.warehouses.view'); }
    public function view(Authenticatable $user, Warehouse $warehouse): bool { return $this->can($user, 'erp.warehouses.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.warehouses.create'); }
    public function update(Authenticatable $user, Warehouse $warehouse): bool { return $this->can($user, 'erp.warehouses.update'); }
    public function delete(Authenticatable $user, Warehouse $warehouse): bool { return $this->can($user, 'erp.warehouses.delete'); }
}
