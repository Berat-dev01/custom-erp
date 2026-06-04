@extends('erp::layouts.app')

@section('title', __('Bordro Detayı'))
@section('page-title', __('Bordro Detayı'))

@section('content')
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <x-admin-panel::button href="{{ route('erp.payslips.pdf', $payslip) }}" variant="outline" icon="download" target="_blank">{{ __('PDF İndir') }}</x-admin-panel::button>
        <x-admin-panel::button href="{{ route('erp.payroll-runs.show', $payslip->payroll_run_id) }}" variant="ghost" icon="arrow-left">{{ __('Bordroya Dön') }}</x-admin-panel::button>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ $payslip->employee?->full_name }}</h6>
                <table class="table table-sm">
                    <tr><th>{{ __('Sicil No') }}</th><td>{{ $payslip->employee?->employee_number }}</td></tr>
                    <tr><th>{{ __('Departman') }}</th><td>{{ $payslip->employee?->department?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Pozisyon') }}</th><td>{{ $payslip->employee?->position?->name ?? '-' }}</td></tr>
                    <tr><th>{{ __('Dönem') }}</th><td>{{ $payslip->payrollRun?->periodLabel() }}</td></tr>
                    <tr><th>{{ __('Durum') }}</th><td>
                        <x-admin-panel::badge variant="{{ $payslip->status === 'paid' ? 'success' : ($payslip->status === 'approved' ? 'info' : 'secondary') }}">
                            {{ __($payslip->status) }}
                        </x-admin-panel::badge>
                    </td></tr>
                </table>
            </x-admin-panel::card>
        </div>

        <div class="col-md-7">
            <x-admin-panel::card>
                <h6 class="fw-semibold mb-3">{{ __('Bordro Hesabı') }}</h6>
                @php
                    $bd = $payslip->breakdown ?? [];
                    $sgkRate  = isset($bd['sgk_worker'], $bd['gross'])
                        ? (round($bd['sgk_worker'] / max($bd['gross'], 1) * 100, 1).'%')
                        : '%14';
                    $issRate  = isset($bd['unemployment_worker'], $bd['gross'])
                        ? (round($bd['unemployment_worker'] / max($bd['gross'], 1) * 100, 1).'%')
                        : '%1';
                @endphp
                <table class="table table-sm">
                    <tr class="table-light"><td colspan="2"><strong>{{ __('Kazançlar') }}</strong></td></tr>
                    <tr><td>{{ __('Temel Maaş (Brüt)') }}</td><td class="text-end">{{ $erpFormat->money($bd['gross'] ?? $payslip->gross_salary) }}</td></tr>
                    <tr class="fw-semibold"><td>{{ __('Brüt Toplam') }}</td><td class="text-end">{{ $erpFormat->money($bd['gross'] ?? $payslip->gross_salary) }}</td></tr>

                    <tr class="table-light"><td colspan="2"><strong>{{ __('Kesintiler') }}</strong></td></tr>
                    <tr><td>(-) {{ __('SGK İşçi Payı') }} ({{ $sgkRate }})</td>
                        <td class="text-end text-danger">-{{ $erpFormat->money($bd['sgk_worker'] ?? 0) }}</td></tr>
                    <tr><td>(-) {{ __('İşsizlik Sigortası İşçi') }} ({{ $issRate }})</td>
                        <td class="text-end text-danger">-{{ $erpFormat->money($bd['unemployment_worker'] ?? 0) }}</td></tr>
                    @if(isset($bd['income_tax_base']))
                        <tr class="text-muted small"><td>&nbsp;&nbsp;= {{ __('Gelir Vergisi Matrahı') }}</td>
                            <td class="text-end">{{ $erpFormat->money($bd['income_tax_base']) }}</td></tr>
                    @endif
                    <tr><td>(-) {{ __('Gelir Vergisi') }}</td>
                        <td class="text-end text-danger">-{{ $erpFormat->money($bd['income_tax'] ?? 0) }}</td></tr>
                    <tr><td>(-) {{ __('Damga Vergisi') }} (%0.759)</td>
                        <td class="text-end text-danger">-{{ $erpFormat->money($bd['stamp_tax'] ?? 0) }}</td></tr>
                    @if(($bd['agi'] ?? 0) > 0)
                        <tr><td>(+) {{ __('AGİ (Asgari Geçim İndirimi)') }}</td>
                            <td class="text-end text-success">+{{ $erpFormat->money($bd['agi']) }}</td></tr>
                    @endif
                    <tr class="fw-semibold"><td>{{ __('Toplam Kesinti') }}</td>
                        <td class="text-end text-danger">-{{ $erpFormat->money($payslip->total_deductions) }}</td></tr>

                    <tr class="table-success fw-bold fs-6">
                        <td>{{ __('NET ÖDEME') }}</td>
                        <td class="text-end">{{ $erpFormat->money($payslip->net_salary) }}</td>
                    </tr>

                    @if(isset($bd['employer_cost']))
                        <tr class="table-light small text-muted">
                            <td>{{ __('İşveren Maliyeti (brüt + işveren SGK)') }}</td>
                            <td class="text-end">{{ $erpFormat->money($bd['employer_cost']) }}</td>
                        </tr>
                    @endif
                </table>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
