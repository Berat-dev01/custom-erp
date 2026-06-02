<?php

namespace App\Erp\Policies;

use App\Erp\Models\PurchaseOrder;
use Illuminate\Contracts\Auth\Authenticatable;

class PurchaseOrderPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.purchase_orders.view'); }
    public function view(Authenticatable $user, PurchaseOrder $po): bool { return $this->can($user, 'erp.purchase_orders.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.purchase_orders.create'); }
    public function update(Authenticatable $user, PurchaseOrder $po): bool { return $po->isDraft() && $this->can($user, 'erp.purchase_orders.update'); }
    public function delete(Authenticatable $user, PurchaseOrder $po): bool { return $po->isDraft() && $this->can($user, 'erp.purchase_orders.delete'); }
    public function approve(Authenticatable $user, PurchaseOrder $po): bool { return $this->can($user, 'erp.purchase_orders.approve'); }
    public function receive(Authenticatable $user, PurchaseOrder $po): bool { return $this->can($user, 'erp.purchase_orders.receive'); }
}
