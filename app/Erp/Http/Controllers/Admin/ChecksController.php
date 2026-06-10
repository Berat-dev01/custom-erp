<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\Check;
use App\Erp\Models\Customer;
use App\Erp\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChecksController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('erp.bank.view');

        $query = Check::query()
            ->when($request->get('type'),   fn ($q, $v) => $q->where('type', $v))
            ->when($request->get('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->get('date_from'), fn ($q, $v) => $q->where('due_date', '>=', $v))
            ->when($request->get('date_to'),   fn ($q, $v) => $q->where('due_date', '<=', $v))
            ->orderBy('due_date')
            ->orderBy('id');

        $checks = $query->paginate(30)->withQueryString();

        $dueSoon = Check::whereNotIn('status', ['cashed', 'cancelled'])
            ->whereBetween('due_date', [today(), today()->addDays(7)])
            ->count();

        return view('erp::admin.checks.index', compact('checks', 'dueSoon'));
    }

    public function create()
    {
        Gate::authorize('erp.bank.manage');

        $customers = Customer::where('status', 'active')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();

        return view('erp::admin.checks.create', compact('customers', 'suppliers'));
    }

    public function store(Request $request)
    {
        Gate::authorize('erp.bank.manage');

        $data = $request->validate([
            'type'         => ['required', 'in:received,issued'],
            'check_number' => ['required', 'string', 'max:50'],
            'bank_name'    => ['required', 'string', 'max:100'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'issue_date'   => ['required', 'date'],
            'due_date'     => ['required', 'date', 'after_or_equal:issue_date'],
            'party_type'   => ['required', 'in:erp_customer,erp_supplier'],
            'party_id'     => ['required', 'integer'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        Check::create([
            ...$data,
            'status'     => 'portfolio',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('erp.checks.index')
            ->with('erp_status', __('Çek/senet kaydedildi.'));
    }

    public function updateStatus(Request $request, Check $check)
    {
        Gate::authorize('erp.bank.manage');

        $data = $request->validate([
            'status' => ['required', 'in:portfolio,sent_to_bank,cashed,bounced,cancelled'],
        ]);

        $check->update($data);

        return back()->with('erp_status', __('Durum güncellendi.'));
    }

    public function destroy(Check $check)
    {
        Gate::authorize('erp.bank.manage');

        abort_if(in_array($check->status, ['cashed']), 422, __('Tahsil edilmiş çek silinemez.'));

        $check->delete();

        return back()->with('erp_status', __('Çek/senet silindi.'));
    }
}
