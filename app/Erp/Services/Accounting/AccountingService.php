<?php

namespace App\Erp\Services\Accounting;

use App\Erp\Models\Account;
use App\Erp\Models\DepreciationEntry;
use App\Erp\Models\Invoice;
use App\Erp\Models\JournalEntry;
use App\Erp\Models\JournalLine;
use App\Erp\Models\Payment;
use App\Erp\Models\PayrollRun;
use App\Erp\Models\PurchaseOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    private function generateEntryNumber(): string
    {
        $year = now()->year;
        $last = JournalEntry::where('entry_number', 'like', "YEV-{$year}-%")->count();

        return sprintf('YEV-%d-%05d', $year, $last + 1);
    }

    private function findAccount(string $code): ?Account
    {
        return Account::where('code', $code)->where('is_active', true)->first();
    }

    /**
     * Core method: create a journal entry with lines and mark as posted.
     *
     * @param array{description: string, type: string, entry_date: string, reference?: string, source_type?: string, source_id?: int, created_by: int, lines: list<array{account_code: string, debit: float, credit: float, description?: string}>} $data
     */
    public function postJournalEntry(array $data): ?JournalEntry
    {
        try {
            return DB::transaction(function () use ($data): JournalEntry {
                $entry = JournalEntry::create([
                    'entry_number' => $this->generateEntryNumber(),
                    'entry_date'   => $data['entry_date'] ?? today()->toDateString(),
                    'type'         => $data['type'],
                    'description'  => $data['description'],
                    'reference'    => $data['reference'] ?? null,
                    'source_type'  => $data['source_type'] ?? null,
                    'source_id'    => $data['source_id'] ?? null,
                    'status'       => 'posted',
                    'created_by'   => $data['created_by'],
                ]);

                foreach ($data['lines'] as $line) {
                    $account = $this->findAccount($line['account_code']);

                    if (! $account) {
                        Log::warning('AccountingService: account not found', ['code' => $line['account_code']]);
                        continue;
                    }

                    JournalLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $account->id,
                        'debit'            => $line['debit'] ?? 0,
                        'credit'           => $line['credit'] ?? 0,
                        'description'      => $line['description'] ?? null,
                    ]);
                }

                return $entry;
            });
        } catch (\Throwable $e) {
            Log::error('AccountingService::postJournalEntry failed', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);

            return null;
        }
    }

    /**
     * Satış faturası kesildiğinde:
     * Borç: 120 Alıcılar (brüt tutar)
     * Alacak: 600 Yurt İçi Satışlar (KDV hariç)
     * Alacak: 391 Hesaplanan KDV
     */
    public function postSaleInvoice(Invoice $invoice): ?JournalEntry
    {
        $subtotal = (float) $invoice->subtotal - (float) ($invoice->discount_amount ?? 0);
        $tax      = (float) $invoice->tax_amount;
        $total    = (float) $invoice->total;

        if ($total <= 0) {
            return null;
        }

        return $this->postJournalEntry([
            'type'        => 'invoice',
            'description' => "Satış Faturası: {$invoice->invoice_number}",
            'reference'   => $invoice->invoice_number,
            'entry_date'  => $invoice->issue_date->toDateString(),
            'source_type' => 'erp_invoice',
            'source_id'   => $invoice->id,
            'created_by'  => $invoice->created_by,
            'lines'       => [
                ['account_code' => '120', 'debit' => $total,    'credit' => 0,        'description' => $invoice->invoice_number],
                ['account_code' => '600', 'debit' => 0,         'credit' => $subtotal,'description' => 'Satış hasılatı'],
                ['account_code' => '391', 'debit' => 0,         'credit' => $tax,     'description' => 'Hesaplanan KDV'],
            ],
        ]);
    }

    /**
     * Ödeme alındığında:
     * Borç: 102 Bankalar
     * Alacak: 120 Alıcılar
     */
    public function postPaymentReceived(Payment $payment): ?JournalEntry
    {
        $amount = (float) $payment->amount;

        if ($amount <= 0) {
            return null;
        }

        $invoice = $payment->invoice;

        return $this->postJournalEntry([
            'type'        => 'payment',
            'description' => "Tahsilat: {$invoice?->invoice_number}",
            'reference'   => $invoice?->invoice_number,
            'entry_date'  => $payment->payment_date->toDateString(),
            'source_type' => 'erp_payment',
            'source_id'   => $payment->id,
            'created_by'  => $payment->created_by,
            'lines'       => [
                ['account_code' => '102', 'debit' => $amount, 'credit' => 0,      'description' => 'Banka tahsilatı'],
                ['account_code' => '120', 'debit' => 0,       'credit' => $amount,'description' => 'Alacak kapatma'],
            ],
        ]);
    }

    /**
     * Satın alma mal teslimi alındığında:
     * Borç: 153 Ticari Mallar (KDV hariç)
     * Borç: 191 İndirilecek KDV
     * Alacak: 320 Satıcılar (brüt)
     */
    public function postPurchaseInvoice(PurchaseOrder $po): ?JournalEntry
    {
        $subtotal = (float) $po->subtotal;
        $tax      = (float) $po->tax_amount;
        $total    = (float) $po->total;

        if ($total <= 0) {
            return null;
        }

        return $this->postJournalEntry([
            'type'        => 'invoice',
            'description' => "Alış: {$po->po_number}",
            'reference'   => $po->po_number,
            'entry_date'  => ($po->received_date ?? today())->toDateString(),
            'source_type' => 'erp_purchase_order',
            'source_id'   => $po->id,
            'created_by'  => $po->created_by,
            'lines'       => [
                ['account_code' => '153', 'debit' => $subtotal, 'credit' => 0,     'description' => 'Stok girişi'],
                ['account_code' => '191', 'debit' => $tax,      'credit' => 0,     'description' => 'İndirilecek KDV'],
                ['account_code' => '320', 'debit' => 0,          'credit' => $total,'description' => $po->po_number],
            ],
        ]);
    }

    /**
     * Bordro onaylandığında:
     * Borç: 770 Genel Yönetim Giderleri (brüt toplam)
     * Alacak: 335 Personele Borçlar (net toplam)
     * Alacak: 360 Ödenecek Vergiler (kesintiler)
     */
    public function postPayroll(PayrollRun $run): ?JournalEntry
    {
        $gross       = (float) $run->total_gross;
        $net         = (float) $run->total_net;
        $deductions  = (float) $run->total_deductions;

        if ($gross <= 0) {
            return null;
        }

        return $this->postJournalEntry([
            'type'        => 'payroll',
            'description' => "Bordro: {$run->year}/{$run->month}",
            'reference'   => "{$run->year}-{$run->month}",
            'entry_date'  => ($run->pay_date ?? today())->toDateString(),
            'source_type' => 'erp_payroll_run',
            'source_id'   => $run->id,
            'created_by'  => $run->created_by,
            'lines'       => [
                ['account_code' => '770', 'debit' => $gross,      'credit' => 0,          'description' => 'Personel gideri'],
                ['account_code' => '335', 'debit' => 0,            'credit' => $net,       'description' => 'Net maaş borcu'],
                ['account_code' => '360', 'debit' => 0,            'credit' => $deductions,'description' => 'SGK/Vergi kesintileri'],
            ],
        ]);
    }

    /**
     * Aylık amortisman:
     * Borç: 770 Genel Yönetim Giderleri (amortisman tutarı)
     * Alacak: 257 Birikmiş Amortismanlar
     */
    public function postDepreciation(DepreciationEntry $entry): ?JournalEntry
    {
        $amount = (float) $entry->amount;

        if ($amount <= 0) {
            return null;
        }

        $asset = $entry->asset;

        return $this->postJournalEntry([
            'type'        => 'depreciation',
            'description' => "Amortisman: {$asset?->name} {$entry->year}/{$entry->month}",
            'reference'   => "{$entry->year}-{$entry->month}",
            'entry_date'  => Carbon::create($entry->year, $entry->month, 1)->endOfMonth()->toDateString(),
            'source_type' => 'erp_depreciation_entry',
            'source_id'   => $entry->id,
            'created_by'  => auth()->id() ?? 1,
            'lines'       => [
                ['account_code' => '770', 'debit' => $amount, 'credit' => 0,      'description' => "Amortisman: {$asset?->name}"],
                ['account_code' => '257', 'debit' => 0,       'credit' => $amount,'description' => 'Birikmiş amortisman'],
            ],
        ]);
    }

    public function accountBalance(int $accountId, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $account = Account::find($accountId);

        if (! $account) {
            return 0;
        }

        return $account->balance($from?->toDateString(), $to?->toDateString());
    }

    public function trialBalance(Carbon $from, Carbon $to): Collection
    {
        return Account::where('is_active', true)
            ->whereHas('journalLines', fn ($q) => $q->whereHas('journalEntry',
                fn ($q2) => $q2->where('status', 'posted')
                    ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ))
            ->with(['journalLines' => fn ($q) => $q->whereHas('journalEntry',
                fn ($q2) => $q2->where('status', 'posted')
                    ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            )])
            ->orderBy('code')
            ->get()
            ->map(function (Account $account): array {
                $debit  = (float) $account->journalLines->sum('debit');
                $credit = (float) $account->journalLines->sum('credit');

                return [
                    'code'           => $account->code,
                    'name'           => $account->name,
                    'type'           => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'total_debit'    => $debit,
                    'total_credit'   => $credit,
                    'balance'        => $account->isDebitNormal() ? ($debit - $credit) : ($credit - $debit),
                ];
            });
    }

    public function balanceSheet(Carbon $date): array
    {
        $accounts = Account::where('is_active', true)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->get();

        $result = ['asset' => [], 'liability' => [], 'equity' => [], 'totals' => []];

        foreach ($accounts as $account) {
            $balance = $account->balance(null, $date->toDateString());
            if (abs($balance) < 0.01) {
                continue;
            }

            $result[$account->type][] = [
                'code'    => $account->code,
                'name'    => $account->name,
                'balance' => $balance,
            ];
        }

        $result['totals'] = [
            'total_assets'      => collect($result['asset'])->sum('balance'),
            'total_liabilities' => collect($result['liability'])->sum('balance'),
            'total_equity'      => collect($result['equity'])->sum('balance'),
        ];

        return $result;
    }

    public function incomeStatement(Carbon $from, Carbon $to): array
    {
        $accounts = Account::where('is_active', true)
            ->whereIn('type', ['revenue', 'expense'])
            ->get();

        $revenue  = [];
        $expenses = [];

        foreach ($accounts as $account) {
            $balance = $account->balance($from->toDateString(), $to->toDateString());
            if (abs($balance) < 0.01) {
                continue;
            }

            if ($account->type === 'revenue') {
                $revenue[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
            } else {
                $expenses[] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
            }
        }

        $totalRevenue  = collect($revenue)->sum('balance');
        $totalExpenses = collect($expenses)->sum('balance');

        return [
            'revenue'        => $revenue,
            'expenses'       => $expenses,
            'total_revenue'  => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit'     => $totalRevenue - $totalExpenses,
        ];
    }

    public function vatReport(int $year, int $month): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $to   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $inbound  = $this->getAccountTotal('191', $from, $to);  // İndirilecek KDV (alış)
        $outbound = $this->getAccountTotal('391', $from, $to);  // Hesaplanan KDV (satış)
        $payable  = $outbound - $inbound;

        return [
            'year'             => $year,
            'month'            => $month,
            'inbound_vat'      => $inbound,
            'outbound_vat'     => $outbound,
            'payable_vat'      => $payable,
            'refundable_vat'   => $payable < 0 ? abs($payable) : 0,
        ];
    }

    private function getAccountTotal(string $code, string $from, string $to): float
    {
        $account = $this->findAccount($code);

        if (! $account) {
            return 0;
        }

        $lines = $account->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted')
                ->whereBetween('entry_date', [$from, $to]))
            ->get();

        $debit  = (float) $lines->sum('debit');
        $credit = (float) $lines->sum('credit');

        return $account->isDebitNormal() ? ($debit - $credit) : ($credit - $debit);
    }
}
