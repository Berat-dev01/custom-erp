<?php

namespace App\Erp\Policies;

use App\Erp\Models\Expense;
use Illuminate\Contracts\Auth\Authenticatable;

class ExpensePolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.expenses.view'); }
    public function view(Authenticatable $user, Expense $expense): bool { return $this->can($user, 'erp.expenses.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.expenses.create'); }
    public function update(Authenticatable $user, Expense $expense): bool { return $this->can($user, 'erp.expenses.update'); }
    public function delete(Authenticatable $user, Expense $expense): bool { return $this->can($user, 'erp.expenses.delete'); }
}
