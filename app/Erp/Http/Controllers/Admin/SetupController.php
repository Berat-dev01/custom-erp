<?php

namespace App\Erp\Http\Controllers\Admin;

use App\Erp\Models\ErpSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class SetupController extends Controller
{
    public function index()
    {
        if ($this->isCompleted()) {
            return redirect()->route('erp.dashboard');
        }

        return view('erp::admin.setup.index');
    }

    public function store(Request $request)
    {
        $step = (int) $request->input('step', 1);

        return match ($step) {
            1 => $this->handleStep1($request),
            2 => $this->handleStep2($request),
            default => redirect()->route('erp.setup'),
        };
    }

    private function handleStep1(Request $request)
    {
        $data = $request->validate([
            'company_name'    => ['required', 'string', 'max:255'],
            'company_email'   => ['nullable', 'email', 'max:255'],
            'company_phone'   => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:500'],
        ]);

        ErpSetting::updateOrCreate(['id' => 1], array_merge($data, ['setup_completed' => false]));

        return redirect()->route('erp.setup')->with('step', 2)->with('success', __('Şirket bilgileri kaydedildi.'));
    }

    private function handleStep2(Request $request)
    {
        $data = $request->validate([
            'currency'        => ['required', 'string', 'size:3'],
            'default_tax_rate'=> ['required', 'numeric', 'min:0', 'max:100'],
            'invoice_prefix'  => ['required', 'string', 'max:20'],
        ]);

        ErpSetting::updateOrCreate(['id' => 1], array_merge($data, ['setup_completed' => true]));

        return redirect()->route('erp.dashboard')->with('success', __('Kurulum tamamlandı! ERP\'ye hoş geldiniz.'));
    }

    private function isCompleted(): bool
    {
        return (bool) optional(ErpSetting::first())->setup_completed;
    }
}
