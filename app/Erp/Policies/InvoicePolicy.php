<?php

namespace App\Erp\Policies;

use App\Erp\Models\Invoice;
use Illuminate\Contracts\Auth\Authenticatable;

class InvoicePolicy extends ErpPolicy
{
    public function viewAny(Authenticatable $user): bool { return $this->can($user, 'erp.invoices.view'); }
    public function view(Authenticatable $user, Invoice $invoice): bool { return $this->can($user, 'erp.invoices.view'); }
    public function create(Authenticatable $user): bool { return $this->can($user, 'erp.invoices.create'); }
    public function update(Authenticatable $user, Invoice $invoice): bool { return $invoice->status === 'draft' && $this->can($user, 'erp.invoices.update'); }
    public function delete(Authenticatable $user, Invoice $invoice): bool { return $invoice->status === 'draft' && $this->can($user, 'erp.invoices.delete'); }
    public function send(Authenticatable $user, Invoice $invoice): bool { return $this->can($user, 'erp.invoices.send'); }
    public function recordPayment(Authenticatable $user, Invoice $invoice): bool { return $this->can($user, 'erp.payments.create'); }
}
