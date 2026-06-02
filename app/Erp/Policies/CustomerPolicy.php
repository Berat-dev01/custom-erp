<?php

namespace App\Erp\Policies;

use App\Erp\Models\Customer;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.customers.view'); }
    public function view(Authenticatable $user, Customer $customer): bool { return $this->can($user, 'erp.customers.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.customers.create'); }
    public function update(Authenticatable $user, Customer $customer): bool { return $this->can($user, 'erp.customers.update'); }
    public function delete(Authenticatable $user, Customer $customer): bool { return $this->can($user, 'erp.customers.delete'); }
}
