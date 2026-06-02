@extends('erp::layouts.app')

@section('title', __('Bordro Detayı'))
@section('page-title', __('Bordro Detayı'))

@section('content')
    <div class="d-flex gap-2 mb-3">
        <x-admin-panel::button href="{{ route('erp.payslips.pdf', $payslip) }}" variant="outline" icon="download" target="_blank">{{ __('PDF İndir') }}</x-admin-panel::button>
        <x-admin-panel::button href="{{ route('erp.payroll-runs.show', $payslip->payroll_run_id) }}" variant="ghost">{{ __('← Bordroya Dön') }}</x-admin-panel::button>
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
                @php $bd = $payslip->breakdown ?? []; @endphp
                <table class="table table-sm">
                    <tr class="table-light"><td colspan="2"><strong>{{ __('Kazançlar') }}</strong></td></tr>
                    <tr><td>{{ __('Temel Maaş (Brüt)') }}</td><td class="text-end">{{ $erpFormat->money($bd['basic_salary'] ?? $payslip->basic_salary) }}</td></tr>
                    <tr class="fw-semibold"><td>{{ __('Brüt Toplam') }}</td><td class="text-end">{{ $erpFormat->money($bd['gross_salary'] ?? $payslip->gross_salary) }}</td></tr>

                    <tr class="table-light"><td colspan="2"><strong>{{ __('Kesintiler') }}</strong></td></tr>
                    @if(isset($bd['sgk_worker']))
                        <tr><td>{{ __('SGK İşçi Payı (%14)') }}</td><td class="text-end text-danger">-{{ $erpFormat->money($bd['sgk_worker']) }}</td></tr>
                    @endif
                    @if(isset($bd['unemployment_worker']))
                        <tr><td>{{ __('İşsizlik İşçi (%1)') }}</td><td class="text-end text-danger">-{{ $erpFormat->money($bd['unemployment_worker']) }}</td></tr>
                    @endif
                    @if(isset($bd['income_tax']))
                        <tr><td>{{ __('Gelir Vergisi') }}</td><td class="text-end text-danger">-{{ $erpFormat->money($bd['income_tax']) }}</td></tr>
                    @endif
                    @if(isset($bd['stamp_tax']))
                        <tr><td>{{ __('Damga Vergisi (%0.759)') }}</td><td class="text-end text-danger">-{{ $erpFormat->money($bd['stamp_tax']) }}</td></tr>
                    @endif
                    <tr class="fw-semibold"><td>{{ __('Toplam Kesinti') }}</td><td class="text-end text-danger">-{{ $erpFormat->money($payslip->total_deductions) }}</td></tr>

                    <tr class="table-success fw-bold fs-6"><td>{{ __('NET ÖDEME') }}</td><td class="text-end">{{ $erpFormat->money($payslip->net_salary) }}</td></tr>
                </table>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
