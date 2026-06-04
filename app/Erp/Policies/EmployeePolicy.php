<?php

namespace App\Erp\Policies;

use App\Erp\Models\Employee;
use Illuminate\Contracts\Auth\Authenticatable;

class EmployeePolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.employees.view'); }
    public function view(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.employees.create'); }
    public function update(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.update'); }
    public function delete(Authenticatable $user, Employee $employee): bool { return $this->can($user, 'erp.employees.delete'); }
}
