<?php

namespace App\Erp\Policies;

use App\Erp\Models\Department;
use Illuminate\Contracts\Auth\Authenticatable;

class DepartmentPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.departments.view'); }
    public function view(Authenticatable $user, Department $department): bool { return $this->can($user, 'erp.departments.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.departments.create'); }
    public function update(Authenticatable $user, Department $department): bool { return $this->can($user, 'erp.departments.update'); }
    public function delete(Authenticatable $user, Department $department): bool { return $this->can($user, 'erp.departments.delete'); }
}
