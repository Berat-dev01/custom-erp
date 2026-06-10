<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreExpenseRequest;
use App\Erp\Http\Requests\UpdateExpenseRequest;
use App\Erp\Models\Expense;
use App\Erp\Services\Expenses\ExpenseQuery;
use App\Erp\Support\ErpExportSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ExpensesController extends Controller
{
    public function __construct(private readonly ExpenseQuery $query) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Expense::class);

        return view('erp::admin.expenses.index', [
            'expenses'      => $this->query->paginate($request),
            'filters'       => $this->query->filters($request),
            'exportColumns' => ErpExportSchema::columns('expenses'),
            'exportFormats' => ErpExportSchema::formats('expenses'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Expense::class);

        return view('erp::admin.expenses.form', ['expense' => new Expense]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data               = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('erp/receipts', 'local');
        }

        unset($data['receipt']);
        Expense::create($data);

        return redirect()
            ->route('erp.expenses.index')
            ->with('erp_status', __('Gider eklendi.'));
    }

    public function edit(Expense $expense): View
    {
        Gate::authorize('update', $expense);

        return view('erp::admin.expenses.form', compact('expense'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store('erp/receipts', 'local');
        }

        unset($data['receipt']);
        $expense->update($data);

        return redirect()
            ->route('erp.expenses.index')
            ->with('erp_status', __('Gider güncellendi.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        Gate::authorize('delete', $expense);

        $expense->delete();

        return redirect()
            ->route('erp.expenses.index')
            ->with('erp_status', __('Gider silindi.'));
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        Gate::authorize('erp.expenses.delete');

        $validated = $request->validate([
            'record_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'record_ids.*' => ['integer', 'exists:erp_expenses,id'],
        ]);

        $deleted = 0;
        Expense::query()
            ->whereKey($validated['record_ids'])
            ->chunkById(200, function ($expenses) use (&$deleted): void {
                foreach ($expenses as $expense) {
                    $expense->delete();
                    $deleted++;
                }
            });

        return back()->with('erp_status', trans_choice(
            '{0} Hiçbiri silinemedi.|{1} :count gider silindi.|[2,*] :count gider silindi.',
            $deleted, ['count' => $deleted]
        ));
    }
}
