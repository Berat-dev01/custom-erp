<?php

namespace App\Erp\Policies;

use App\Erp\Models\Position;
use Illuminate\Contracts\Auth\Authenticatable;

class PositionPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.positions.view'); }
    public function view(Authenticatable $user, Position $position): bool { return $this->can($user, 'erp.positions.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.positions.create'); }
    public function update(Authenticatable $user, Position $position): bool { return $this->can($user, 'erp.positions.update'); }
    public function delete(Authenticatable $user, Position $position): bool { return $this->can($user, 'erp.positions.delete'); }
}
