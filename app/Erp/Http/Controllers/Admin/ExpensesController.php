<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Http\Requests\StoreExpenseRequest;
use App\Erp\Http\Requests\UpdateExpenseRequest;
use App\Erp\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Expense::class);

        $query = Expense::query();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('expense_date', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('expense_date', '<=', $to);
        }

        $expenses = $query->latest('expense_date')->paginate(20)->withQueryString();

        return view('erp::admin.expenses.index', compact('expenses'));
    }

    public function create()
    {
        Gate::authorize('create', Expense::class);

        return view('erp::admin.expenses.create');
    }

    public function store(StoreExpenseRequest $request)
    {
        $data               = $request->validated();
        $data['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('erp/receipts', 'local');
        }

        unset($data['receipt']);
        Expense::create($data);

        return redirect()->route('erp.expenses.index')
            ->with('success', __('Gider eklendi.'));
    }

    public function edit(Expense $expense)
    {
        Gate::authorize('update', $expense);

        return view('erp::admin.expenses.edit', compact('expense'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
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

        return redirect()->route('erp.expenses.index')
            ->with('success', __('Gider güncellendi.'));
    }

    public function destroy(Expense $expense)
    {
        Gate::authorize('delete', $expense);

        $expense->delete();

        return redirect()->route('erp.expenses.index')
            ->with('success', __('Gider silindi.'));
    }
}
