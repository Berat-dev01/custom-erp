<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Account;
use App\Erp\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class AccountsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.accounts.view');

        $accounts = Account::where('is_active', true)
            ->when($request->get('type'), fn ($q, $v) => $q->where('type', $v))
            ->orderBy('code')
            ->get();

        return view('erp::admin.accounts.index', compact('accounts'));
    }

    public function show(Request $request, Account $account)
    {
        Gate::authorize('erp.accounts.view');

        $query = JournalLine::where('account_id', $account->id)
            ->with(['journalEntry'])
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted')
                ->when($request->get('date_from'), fn ($q, $v) => $q->where('entry_date', '>=', $v))
                ->when($request->get('date_to'),   fn ($q, $v) => $q->where('entry_date', '<=', $v))
            )
            ->latest('id');

        $lines   = $query->paginate(30)->withQueryString();
        $balance = $account->balance($request->get('date_from'), $request->get('date_to'));

        return view('erp::admin.accounts.show', compact('account', 'lines', 'balance'));
    }
}
