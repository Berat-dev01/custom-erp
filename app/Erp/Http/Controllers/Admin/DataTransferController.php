<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Asset;
use App\Erp\Models\Customer;
use App\Erp\Models\Employee;
use App\Erp\Models\Expense;
use App\Erp\Models\Invoice;
use App\Erp\Models\Product;
use App\Erp\Models\Project;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Models\SalesOrder;
use App\Erp\Models\Supplier;
use App\Erp\Services\Export\ExcelExportService;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTransferController extends Controller
{
    public function __construct(private readonly ExcelExportService $excel) {}

    public function export(Request $request, string $module): StreamedResponse
    {
        Gate::authorize("erp.{$module}.export");

        $request->validate([
            'columns'   => 'nullable|array',
            'columns.*' => 'string',
            'ids'       => 'nullable|array',
            'ids.*'     => 'integer',
        ]);

        $requestedColumns = array_filter((array) $request->input('columns', []));
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        $allColumns = ErpExportSchema::columns($module);

        if ($requestedColumns) {
            $keyIndex = array_flip($requestedColumns);
            $columns = array_filter($allColumns, fn ($col) => isset($keyIndex[$col['key']]));
            usort($columns, fn ($a, $b) => $keyIndex[$a['key']] <=> $keyIndex[$b['key']]);
            $columns = array_values($columns);
        } else {
            $columns = array_values(array_filter($allColumns, fn ($col) => $col['default']));
        }

        $columnKeys = array_column($columns, 'key');
        $headers = array_column($columns, 'label');

        $rows = $this->exportRows($module, $request, $ids, $columnKeys);

        return $this->excel->download("{$module}.xlsx", $headers, $rows);
    }

    /**
     * @param list<int>    $ids
     * @param list<string> $columnKeys
     */
    private function exportRows(string $module, Request $request, array $ids, array $columnKeys): \Illuminate\Support\LazyCollection
    {
        return match ($module) {
            'customers'      => $this->exportCustomers($request, $ids, $columnKeys),
            'suppliers'      => $this->exportSuppliers($request, $ids, $columnKeys),
            'products'       => $this->exportProducts($request, $ids, $columnKeys),
            'employees'      => $this->exportEmployees($request, $ids, $columnKeys),
            'assets'         => $this->exportAssets($request, $ids, $columnKeys),
            'expenses'       => $this->exportExpenses($request, $ids, $columnKeys),
            'sales-orders'   => $this->exportSalesOrders($request, $ids, $columnKeys),
            'purchase-orders'=> $this->exportPurchaseOrders($request, $ids, $columnKeys),
            'invoices'       => $this->exportInvoices($request, $ids, $columnKeys),
            'projects'       => $this->exportProjects($request, $ids, $columnKeys),
            default          => \Illuminate\Support\LazyCollection::empty(),
        };
    }

    private function exportCustomers(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Customer::query()
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('name')
            ->lazyById()
            ->map(fn (Customer $c) => $this->pick($keys, [
                'name'               => $c->name,
                'email'              => $c->email,
                'phone'              => $c->phone,
                'tax_number'         => $c->tax_number,
                'contact_person'     => $c->contact_person,
                'address'            => $c->address,
                'payment_terms_days' => $c->payment_terms_days,
                'credit_limit'       => $c->credit_limit,
                'status'             => $c->status,
            ]));
    }

    private function exportSuppliers(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Supplier::query()
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('name')
            ->lazyById()
            ->map(fn (Supplier $s) => $this->pick($keys, [
                'name'               => $s->name,
                'email'              => $s->email,
                'phone'              => $s->phone,
                'tax_number'         => $s->tax_number,
                'contact_person'     => $s->contact_person,
                'payment_terms_days' => $s->payment_terms_days,
                'status'             => $s->status,
            ]));
    }

    private function exportProducts(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Product::query()
            ->with(['category', 'unit'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('name')
            ->lazyById()
            ->map(fn (Product $p) => $this->pick($keys, [
                'sku'            => $p->sku,
                'name'           => $p->name,
                'category'       => $p->category?->name,
                'unit'           => $p->unit?->abbreviation,
                'purchase_price' => $p->purchase_price,
                'sale_price'     => $p->sale_price,
                'tax_rate'       => $p->tax_rate,
                'reorder_point'  => $p->reorder_point,
                'is_active'      => $p->is_active ? 'Aktif' : 'Pasif',
            ]));
    }

    private function exportEmployees(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Employee::query()
            ->with(['department', 'position'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('last_name')
            ->lazyById()
            ->map(fn (Employee $e) => $this->pick($keys, [
                'employee_number' => $e->employee_number,
                'first_name'      => $e->first_name,
                'last_name'       => $e->last_name,
                'email'           => $e->email,
                'department'      => $e->department?->name,
                'position'        => $e->position?->name,
                'hire_date'       => $e->hire_date?->format('d.m.Y'),
                'employment_type' => $e->employment_type,
                'status'          => $e->status,
            ]));
    }

    private function exportAssets(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Asset::query()
            ->with(['category'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('name')
            ->lazyById()
            ->map(fn (Asset $a) => $this->pick($keys, [
                'code'           => $a->code,
                'name'           => $a->name,
                'category'       => $a->category?->name,
                'purchase_date'  => $a->purchase_date?->format('d.m.Y'),
                'purchase_value' => $a->purchase_value,
                'current_value'  => $a->current_value,
                'status'         => $a->status,
            ]));
    }

    private function exportExpenses(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Expense::query()
            ->with(['employee'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('expense_date', 'desc')
            ->lazyById()
            ->map(fn (Expense $e) => $this->pick($keys, [
                'title'        => $e->title,
                'category'     => $e->category,
                'amount'       => $e->amount,
                'expense_date' => $e->expense_date?->format('d.m.Y'),
                'employee'     => $e->employee?->first_name.' '.$e->employee?->last_name,
                'status'       => $e->status,
            ]));
    }

    private function exportSalesOrders(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return SalesOrder::query()
            ->with(['customer'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('order_date', 'desc')
            ->lazyById()
            ->map(fn (SalesOrder $o) => $this->pick($keys, [
                'order_number' => $o->so_number,
                'customer'     => $o->customer?->name,
                'order_date'   => $o->order_date?->format('d.m.Y'),
                'total_amount' => $o->total,
                'status'       => $o->status,
            ]));
    }

    private function exportPurchaseOrders(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return PurchaseOrder::query()
            ->with(['supplier'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('order_date', 'desc')
            ->lazyById()
            ->map(fn (PurchaseOrder $o) => $this->pick($keys, [
                'po_number'    => $o->po_number,
                'supplier'     => $o->supplier?->name,
                'order_date'   => $o->order_date?->format('d.m.Y'),
                'total_amount' => $o->total,
                'status'       => $o->status,
            ]));
    }

    private function exportInvoices(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Invoice::query()
            ->with(['customer'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('issue_date', 'desc')
            ->lazyById()
            ->map(fn (Invoice $i) => $this->pick($keys, [
                'invoice_number' => $i->invoice_number,
                'customer'       => $i->customer?->name,
                'invoice_date'   => $i->issue_date?->format('d.m.Y'),
                'total_amount'   => $i->total,
                'status'         => $i->status,
            ]));
    }

    private function exportProjects(Request $request, array $ids, array $keys): \Illuminate\Support\LazyCollection
    {
        return Project::query()
            ->with(['customer'])
            ->when($ids, fn ($q) => $q->whereKey($ids))
            ->orderBy('name')
            ->lazyById()
            ->map(fn (Project $p) => $this->pick($keys, [
                'name'       => $p->name,
                'customer'   => $p->customer?->name,
                'start_date' => $p->start_date?->format('d.m.Y'),
                'end_date'   => $p->end_date?->format('d.m.Y'),
                'budget'     => $p->budget,
                'status'     => $p->status,
            ]));
    }

    /**
     * @param  list<string>         $keys
     * @param  array<string, mixed> $data
     * @return list<mixed>
     */
    private function pick(array $keys, array $data): array
    {
        return array_map(fn ($key) => $data[$key] ?? null, $keys);
    }
}
