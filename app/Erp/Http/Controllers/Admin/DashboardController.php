<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Employee;
use App\Erp\Models\Invoice;
use App\Erp\Models\Project;
use App\Erp\Models\PurchaseOrder;
use App\Erp\Services\Finance\ExpenseService;
use App\Erp\Services\Finance\InvoiceService;
use App\Erp\Services\Inventory\StockService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private StockService $stockService,
        private ExpenseService $expenseService,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.dashboard.view');

        $revenueThisMonth  = $this->invoiceService->revenueThisMonth();
        $revenueLastMonth  = $this->invoiceService->revenueLastMonth();
        $revenueTrend      = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : null;

        return view('erp::admin.dashboard.index', [
            'revenue_this_month'   => $revenueThisMonth,
            'revenue_trend'        => $revenueTrend,
            'outstanding_invoices' => $this->invoiceService->outstandingTotal(),
            'overdue_invoices'     => $this->invoiceService->overdueTotal(),
            'expenses_this_month'  => $this->expenseService->thisMonth(),
            'open_purchase_orders' => PurchaseOrder::whereNotIn('status', ['received', 'cancelled'])->count(),
            'low_stock_products'   => $this->stockService->lowStockCount(),
            'active_employees'     => Employee::where('status', 'active')->count(),
            'active_projects'      => Project::where('status', 'active')->count(),
            'recent_invoices'      => Invoice::with('invoiceable')->latest()->limit(5)->get(),
        ]);
    }
}
