<?php

namespace App\Erp\Policies;

use App\Erp\Models\Asset;
use Illuminate\Contracts\Auth\Authenticatable;

class AssetPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.assets.view'); }
    public function view(Authenticatable $user, Asset $asset): bool { return $this->can($user, 'erp.assets.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.assets.create'); }
    public function update(Authenticatable $user, Asset $asset): bool { return $this->can($user, 'erp.assets.update'); }
    public function delete(Authenticatable $user, Asset $asset): bool { return $this->can($user, 'erp.assets.delete'); }
}
