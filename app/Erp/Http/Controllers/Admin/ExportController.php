<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Employee;
use App\Erp\Models\Invoice;
use App\Erp\Models\PayrollRun;
use App\Erp\Models\Product;
use App\Erp\Models\SalesOrder;
use App\Erp\Services\Accounting\AccountingService;
use App\Erp\Services\Export\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    public function __construct(
        private ExcelExportService $excel,
        private AccountingService  $accounting,
    ) {}

    public function employees(Request $request)
    {
        Gate::authorize('erp.employees.export');

        $employees = Employee::with(['department', 'position'])
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('last_name')
            ->limit(10000)
            ->get();

        return $this->excel->download('calisanlar.xlsx',
            ['Sicil No', 'Ad', 'Soyad', 'E-posta', 'Departman', 'Pozisyon', 'İşe Giriş', 'Çalışma Tipi', 'Durum'],
            $employees->map(fn ($e) => [
                $e->employee_number, $e->first_name, $e->last_name, $e->email,
                $e->department?->name, $e->position?->name,
                $e->hire_date?->format('d.m.Y'), $e->employment_type, $e->status,
            ])
        );
    }

    public function products(Request $request)
    {
        Gate::authorize('erp.products.export');

        $products = Product::with(['category', 'unit', 'stockLevels'])
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(10000)
            ->get();

        return $this->excel->download('urunler.xlsx',
            ['SKU', 'Ad', 'Kategori', 'Birim', 'Alış Fiyatı', 'Satış Fiyatı', 'KDV%', 'Toplam Stok', 'Reorder Noktası'],
            $products->map(fn ($p) => [
                $p->sku, $p->name, $p->category?->name, $p->unit?->abbreviation,
                number_format((float) $p->purchase_price, 2, ',', '.'),
                number_format((float) $p->sale_price, 2, ',', '.'),
                $p->tax_rate, $p->totalStock(), $p->reorder_point,
            ])
        );
    }

    public function invoices(Request $request)
    {
        Gate::authorize('erp.invoices.export');

        $invoices = Invoice::with('invoiceable')
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('date_from'), fn ($q, $v) => $q->where('issue_date', '>=', $v))
            ->when($request->get('date_to'),   fn ($q, $v) => $q->where('issue_date', '<=', $v))
            ->latest('issue_date')
            ->limit(10000)
            ->get();

        return $this->excel->download('faturalar.xlsx',
            ['Fatura No', 'Tip', 'Müşteri/Tedarikçi', 'Tarih', 'Vade', 'Alt Toplam', 'KDV', 'Toplam', 'Ödenen', 'Kalan', 'Durum'],
            $invoices->map(fn ($inv) => [
                $inv->invoice_number, $inv->type,
                $inv->invoiceable?->name,
                $inv->issue_date?->format('d.m.Y'), $inv->due_date?->format('d.m.Y'),
                number_format((float) $inv->subtotal, 2, ',', '.'),
                number_format((float) $inv->tax_amount, 2, ',', '.'),
                number_format((float) $inv->total, 2, ',', '.'),
                number_format((float) $inv->paid_amount, 2, ',', '.'),
                number_format($inv->remainingAmount(), 2, ',', '.'),
                $inv->status,
            ])
        );
    }

    public function salesOrders(Request $request)
    {
        Gate::authorize('erp.sales_orders.view');

        $orders = SalesOrder::with('customer')
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('order_date')
            ->limit(10000)
            ->get();

        return $this->excel->download('satis-siparisleri.xlsx',
            ['SO No', 'Müşteri', 'Sipariş Tarihi', 'Teslim Tarihi', 'Toplam', 'Durum'],
            $orders->map(fn ($o) => [
                $o->so_number, $o->customer?->name,
                $o->order_date?->format('d.m.Y'), $o->actual_delivery_date?->format('d.m.Y'),
                number_format((float) $o->total, 2, ',', '.'), $o->status,
            ])
        );
    }

    public function trialBalance(Request $request)
    {
        Gate::authorize('erp.reports.view');

        $from = Carbon::parse($request->get('date_from', now()->startOfYear()));
        $to   = Carbon::parse($request->get('date_to',   now()));
        $rows = $this->accounting->trialBalance($from, $to);

        return $this->excel->download('mizan.xlsx',
            ['Hesap Kodu', 'Hesap Adı', 'Tip', 'Borç', 'Alacak', 'Bakiye'],
            $rows->map(fn ($r) => [
                $r['code'], $r['name'], $r['type'],
                number_format($r['total_debit'], 2, ',', '.'),
                number_format($r['total_credit'], 2, ',', '.'),
                number_format($r['balance'], 2, ',', '.'),
            ])
        );
    }

    public function payrollSummary(PayrollRun $payrollRun)
    {
        Gate::authorize('erp.payroll.view');

        $payrollRun->loadMissing('payslips.employee');

        return $this->excel->download("bordro-{$payrollRun->year}-{$payrollRun->month}.xlsx",
            ['Sicil No', 'Ad Soyad', 'Brüt', 'SGK İşçi', 'Gelir Vergisi', 'Damga Vergisi', 'Toplam Kesinti', 'Net'],
            $payrollRun->payslips->map(function ($ps) {
                $bd = $ps->breakdown ?? [];
                return [
                    $ps->employee?->employee_number,
                    $ps->employee?->full_name,
                    number_format((float) $ps->gross_salary, 2, ',', '.'),
                    number_format($bd['sgk_worker'] ?? 0, 2, ',', '.'),
                    number_format($bd['income_tax'] ?? 0, 2, ',', '.'),
                    number_format($bd['stamp_tax'] ?? 0, 2, ',', '.'),
                    number_format((float) $ps->total_deductions, 2, ',', '.'),
                    number_format((float) $ps->net_salary, 2, ',', '.'),
                ];
            })
        );
    }
}
