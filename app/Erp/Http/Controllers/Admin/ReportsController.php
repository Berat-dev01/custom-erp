<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Department;
use App\Erp\Models\Employee;
use App\Erp\Models\Invoice;
use App\Erp\Models\Product;
use App\Erp\Models\StockLevel;
use App\Erp\Services\Accounting\AccountingService;
use App\Erp\Services\Finance\ExpenseService;
use App\Erp\Services\Finance\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReportsController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private ExpenseService $expenseService,
        private AccountingService $accountingService,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('erp.reports.view');

        return view('erp::admin.reports.index');
    }

    public function revenueReport(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $months = collect(range(11, 0))->map(fn (int $i) => now()->startOfMonth()->subMonths($i));

        $revenueByMonth = $months->map(fn ($m) => (float) Invoice::where('type', 'sale')
            ->whereIn('status', ['paid', 'partial', 'sent', 'overdue'])
            ->whereYear('issue_date', $m->year)
            ->whereMonth('issue_date', $m->month)
            ->sum('total'));

        $expenseByMonth = $months->map(fn ($m) => (float) \App\Erp\Models\Expense::whereYear('expense_date', $m->year)
            ->whereMonth('expense_date', $m->month)
            ->sum('amount'));

        $labels  = $months->map(fn ($m) => $m->format('M Y'))->values();
        $totalRevenue = $revenueByMonth->sum();
        $totalExpense = $expenseByMonth->sum();

        return view('erp::admin.reports.revenue', [
            'labels'          => $labels,
            'revenue_data'    => $revenueByMonth->values(),
            'expense_data'    => $expenseByMonth->values(),
            'total_revenue'   => $totalRevenue,
            'total_expense'   => $totalExpense,
            'net_profit'      => $totalRevenue - $totalExpense,
        ]);
    }

    public function inventoryReport(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $stockByWarehouse = StockLevel::select(
            'erp_warehouses.name as warehouse_name',
            DB::raw('COUNT(DISTINCT erp_stock_levels.product_id) as product_count'),
            DB::raw('SUM(erp_stock_levels.quantity * erp_products.purchase_price) as total_value')
        )
            ->join('erp_warehouses', 'erp_warehouses.id', '=', 'erp_stock_levels.warehouse_id')
            ->join('erp_products', 'erp_products.id', '=', 'erp_stock_levels.product_id')
            ->where('erp_products.track_stock', true)
            ->groupBy('erp_warehouses.id', 'erp_warehouses.name')
            ->get();

        $lowStockProducts = Product::where('track_stock', true)
            ->where('reorder_point', '>', 0)
            ->with(['stockLevels.warehouse', 'unit'])
            ->get()
            ->filter(fn ($p) => $p->stockLevels->sum('quantity') <= $p->reorder_point)
            ->values();

        $totalStockValue = (float) StockLevel::join('erp_products', 'erp_products.id', '=', 'erp_stock_levels.product_id')
            ->where('erp_products.track_stock', true)
            ->sum(DB::raw('erp_stock_levels.quantity * erp_products.purchase_price'));

        return view('erp::admin.reports.inventory', [
            'stock_by_warehouse' => $stockByWarehouse,
            'low_stock_products' => $lowStockProducts,
            'total_stock_value'  => $totalStockValue,
            'low_stock_count'    => $lowStockProducts->count(),
        ]);
    }

    public function hrReport(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $departmentHeadcount = Department::where('is_active', true)
            ->withCount(['employees' => fn ($q) => $q->where('status', 'active')])
            ->orderByDesc('employees_count')
            ->get();

        $recentHires = Employee::where('hire_date', '>=', Carbon::today()->subDays(30))
            ->with(['department', 'position'])
            ->orderByDesc('hire_date')
            ->get();

        $recentTerminations = Employee::where('status', 'terminated')
            ->where('termination_date', '>=', Carbon::today()->subDays(30))
            ->with(['department', 'position'])
            ->orderByDesc('termination_date')
            ->get();

        $totalActive      = Employee::where('status', 'active')->count();
        $totalOnLeave     = Employee::where('status', 'on_leave')->count();
        $totalTerminated  = Employee::where('status', 'terminated')->count();

        $byEmploymentType = Employee::where('status', 'active')
            ->select('employment_type', DB::raw('COUNT(*) as count'))
            ->groupBy('employment_type')
            ->pluck('count', 'employment_type');

        return view('erp::admin.reports.hr', [
            'department_headcount' => $departmentHeadcount,
            'recent_hires'         => $recentHires,
            'recent_terminations'  => $recentTerminations,
            'total_active'         => $totalActive,
            'total_on_leave'       => $totalOnLeave,
            'total_terminated'     => $totalTerminated,
            'by_employment_type'   => $byEmploymentType,
        ]);
    }

    public function agingReport(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $today = Carbon::today();

        $outstanding = Invoice::whereIn('status', ['sent', 'partial', 'overdue'])
            ->selectRaw('*, (total - paid_amount) as remaining, DATEDIFF(?, due_date) as days_overdue', [$today->toDateString()])
            ->with('invoiceable')
            ->orderByDesc('days_overdue')
            ->get();

        $buckets = [
            'current'  => $outstanding->where('days_overdue', '<', 0),
            'days_30'  => $outstanding->whereBetween('days_overdue', [0, 30]),
            'days_60'  => $outstanding->whereBetween('days_overdue', [31, 60]),
            'days_90'  => $outstanding->whereBetween('days_overdue', [61, 90]),
            'days_90p' => $outstanding->where('days_overdue', '>', 90),
        ];

        $bucketTotals = collect($buckets)->map(fn ($b) => $b->sum('remaining'));

        return view('erp::admin.reports.aging', [
            'outstanding'   => $outstanding,
            'buckets'       => $buckets,
            'bucket_totals' => $bucketTotals,
            'grand_total'   => $outstanding->sum('remaining'),
        ]);
    }

    public function trialBalance(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $from = Carbon::parse($request->get('date_from', now()->startOfYear()))->startOfDay();
        $to   = Carbon::parse($request->get('date_to',   now()))->endOfDay();

        $rows = $this->accountingService->trialBalance($from, $to);

        return view('erp::admin.reports.trial-balance', compact('rows', 'from', 'to'));
    }

    public function balanceSheet(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $date = Carbon::parse($request->get('date', now()));
        $data = $this->accountingService->balanceSheet($date);

        return view('erp::admin.reports.balance-sheet', compact('data', 'date'));
    }

    public function incomeStatement(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $from = Carbon::parse($request->get('date_from', now()->startOfYear()));
        $to   = Carbon::parse($request->get('date_to',   now()));
        $data = $this->accountingService->incomeStatement($from, $to);

        return view('erp::admin.reports.income-statement', compact('data', 'from', 'to'));
    }

    public function taxReport(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);
        $data  = $this->accountingService->vatReport($year, $month);

        return view('erp::admin.reports.tax-report', compact('data', 'year', 'month'));
    }
}
