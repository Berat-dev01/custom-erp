<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Account;
use App\Erp\Models\BankAccount;
use App\Erp\Models\BankTransaction;
use App\Erp\Services\Bank\BankService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class BankAccountsController extends Controller
{
    public function __construct(private BankService $bankService) {}

    public function index()
    {
        Gate::authorize('erp.bank.view');

        $accounts = BankAccount::where('is_active', true)
            ->with('account')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => ['account' => $a, 'balance' => $this->bankService->currentBalance($a)]);

        return view('erp::admin.bank-accounts.index', compact('accounts'));
    }

    public function create()
    {
        Gate::authorize('erp.bank.manage');

        $ledgerAccounts = Account::where('is_active', true)
            ->where('type', 'asset')
            ->orderBy('code')
            ->get();

        return view('erp::admin.bank-accounts.create', compact('ledgerAccounts'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.bank.manage');

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'bank_name'       => ['required', 'string', 'max:100'],
            'iban'            => ['nullable', 'string', 'max:26'],
            'account_number'  => ['nullable', 'string', 'max:50'],
            'branch'          => ['nullable', 'string', 'max:100'],
            'currency'        => ['required', 'string', 'size:3'],
            'opening_balance' => ['required', 'numeric'],
            'account_id'      => ['nullable', 'exists:erp_accounts,id'],
        ]);

        BankAccount::create($data);

        return redirect()->route('erp.bank-accounts.index')
            ->with('erp_status', __('Banka hesabı oluşturuldu.'));
    }

    public function show(Request $request, BankAccount $bankAccount)
    {
        Gate::authorize('erp.bank.view');

        $balance = $this->bankService->currentBalance($bankAccount);

        $transactions = BankTransaction::where('bank_account_id', $bankAccount->id)
            ->when($request->get('date_from'), fn ($q, $v) => $q->where('transaction_date', '>=', $v))
            ->when($request->get('date_to'),   fn ($q, $v) => $q->where('transaction_date', '<=', $v))
            ->when($request->get('reconciled') !== null, fn ($q) => $q->where('is_reconciled', (bool) $request->get('reconciled')))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $bankAccounts = BankAccount::where('is_active', true)->where('id', '!=', $bankAccount->id)->orderBy('name')->get();

        return view('erp::admin.bank-accounts.show', compact('bankAccount', 'balance', 'transactions', 'bankAccounts'));
    }

    public function storeTransaction(Request $request, BankAccount $bankAccount)
    {
        Gate::authorize('erp.bank.manage');

        $data = $request->validate([
            'type'             => ['required', 'in:deposit,withdrawal'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description'      => ['nullable', 'string', 'max:255'],
            'reference'        => ['nullable', 'string', 'max:100'],
        ]);

        BankTransaction::create([
            ...$data,
            'bank_account_id' => $bankAccount->id,
            'created_by'      => $request->user()->id,
        ]);

        return back()->with('erp_status', __('İşlem kaydedildi.'));
    }

    public function transfer(Request $request, BankAccount $bankAccount)
    {
        Gate::authorize('erp.bank.manage');

        $data = $request->validate([
            'to_account_id'    => ['required', 'exists:erp_bank_accounts,id', 'different:bank_account_id'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description'      => ['nullable', 'string', 'max:255'],
        ]);

        $toAccount = BankAccount::findOrFail($data['to_account_id']);

        $this->bankService->transfer(
            $bankAccount,
            $toAccount,
            (float) $data['amount'],
            Carbon::parse($data['transaction_date']),
            $data['description'] ?? null,
            $request->user()->id
        );

        return back()->with('erp_status', __('Transfer başarılı.'));
    }

    public function reconcile(Request $request, BankAccount $bankAccount)
    {
        Gate::authorize('erp.bank.manage');

        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];

        $count = $this->bankService->reconcile($bankAccount, $ids);

        return back()->with('erp_status', __(':count işlem mutabık sayıldı.', ['count' => $count]));
    }

    public function importStatement(Request $request, BankAccount $bankAccount)
    {
        Gate::authorize('erp.bank.manage');

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->bankService->importStatement($bankAccount, $request->file('file'), $request->user()->id);

        return back()->with('erp_status', __(':imported işlem içe aktarıldı, :skipped atlandı.', $result));
    }
}
