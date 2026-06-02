<?php

namespace App\Erp\Policies;

use App\Erp\Models\Product;
use Illuminate\Contracts\Auth\Authenticatable;

class ProductPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.products.view'); }
    public function view(Authenticatable $user, Product $product): bool { return $this->can($user, 'erp.products.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.products.create'); }
    public function update(Authenticatable $user, Product $product): bool { return $this->can($user, 'erp.products.update'); }
    public function delete(Authenticatable $user, Product $product): bool { return $this->can($user, 'erp.products.delete'); }
}
