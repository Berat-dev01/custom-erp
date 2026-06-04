<?php

namespace App\Erp\Policies;

use App\Erp\Models\Supplier;
use Illuminate\Contracts\Auth\Authenticatable;

class SupplierPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.suppliers.view'); }
    public function view(Authenticatable $user, Supplier $supplier): bool { return $this->can($user, 'erp.suppliers.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.suppliers.create'); }
    public function update(Authenticatable $user, Supplier $supplier): bool { return $this->can($user, 'erp.suppliers.update'); }
    public function delete(Authenticatable $user, Supplier $supplier): bool { return $this->can($user, 'erp.suppliers.delete'); }
}
