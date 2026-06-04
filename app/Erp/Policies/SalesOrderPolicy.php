<?php

namespace App\Erp\Policies;

use App\Erp\Models\SalesOrder;
use Illuminate\Contracts\Auth\Authenticatable;

class SalesOrderPolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.sales_orders.view'); }
    public function view(Authenticatable $user, SalesOrder $order): bool { return $this->can($user, 'erp.sales_orders.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.sales_orders.create'); }
    public function update(Authenticatable $user, SalesOrder $order): bool { return $order->isDraft() && $this->can($user, 'erp.sales_orders.update'); }
    public function delete(Authenticatable $user, SalesOrder $order): bool { return $order->isDraft() && $this->can($user, 'erp.sales_orders.delete'); }
    public function confirm(Authenticatable $user, SalesOrder $order): bool { return $this->can($user, 'erp.sales_orders.confirm'); }
    public function deliver(Authenticatable $user, SalesOrder $order): bool { return $this->can($user, 'erp.sales_orders.deliver'); }
    public function cancel(Authenticatable $user, SalesOrder $order): bool { return $this->can($user, 'erp.sales_orders.update'); }
    public function createInvoice(Authenticatable $user, SalesOrder $order): bool { return $this->can($user, 'erp.invoices.create'); }
}
