<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Account;
use App\Erp\Models\JournalEntry;
use App\Erp\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class JournalEntriesController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.journal_entries.view');

        $query = JournalEntry::with(['createdBy'])
            ->when($request->get('type'),       fn ($q, $v) => $q->where('type', $v))
            ->when($request->get('status'),     fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('date_from'),  fn ($q, $v) => $q->where('entry_date', '>=', $v))
            ->when($request->get('date_to'),    fn ($q, $v) => $q->where('entry_date', '<=', $v))
            ->when($request->get('search'),     fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->where('entry_number', 'like', "%{$v}%")
                  ->orWhere('description', 'like', "%{$v}%")
                  ->orWhere('reference', 'like', "%{$v}%");
            }))
            ->latest('entry_date')
            ->latest('id');

        $entries = $query->paginate(25)->withQueryString();

        return view('erp::admin.journal-entries.index', compact('entries'));
    }

    public function create()
    {
        Gate::authorize('erp.journal_entries.create');

        $accounts = Account::where('is_active', true)
            ->where('allow_manual_entry', true)
            ->orderBy('code')
            ->get();

        return view('erp::admin.journal-entries.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.journal_entries.create');

        $data = $request->validate([
            'description'              => ['required', 'string', 'max:255'],
            'entry_date'               => ['required', 'date'],
            'reference'                => ['nullable', 'string', 'max:100'],
            'lines'                    => ['required', 'array', 'min:2'],
            'lines.*.account_id'       => ['required', 'exists:erp_accounts,id'],
            'lines.*.debit'            => ['required', 'numeric', 'min:0'],
            'lines.*.credit'           => ['required', 'numeric', 'min:0'],
            'lines.*.description'      => ['nullable', 'string', 'max:255'],
        ]);

        $totalDebit  = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['lines' => __('Borç ve alacak toplamları eşit olmalıdır.')])->withInput();
        }

        try {
            DB::transaction(function () use ($data, $request): void {
                $year = now()->year;
                $last = JournalEntry::where('entry_number', 'like', "YEV-{$year}-%")->count();

                $entry = JournalEntry::create([
                    'entry_number' => sprintf('YEV-%d-%05d', $year, $last + 1),
                    'entry_date'   => $data['entry_date'],
                    'type'         => 'manual',
                    'description'  => $data['description'],
                    'reference'    => $data['reference'] ?? null,
                    'status'       => 'posted',
                    'created_by'   => $request->user()->id,
                ]);

                foreach ($data['lines'] as $line) {
                    JournalLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $line['account_id'],
                        'debit'            => $line['debit'],
                        'credit'           => $line['credit'],
                        'description'      => $line['description'] ?? null,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('JournalEntry store failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => __('Fiş kaydedilirken hata oluştu.')])->withInput();
        }

        return redirect()->route('erp.journal-entries.index')
            ->with('success', __('Yevmiye fişi oluşturuldu.'));
    }

    public function show(JournalEntry $journalEntry)
    {
        Gate::authorize('erp.journal_entries.view');

        $journalEntry->loadMissing(['lines.account', 'createdBy']);

        return view('erp::admin.journal-entries.show', compact('journalEntry'));
    }
}
