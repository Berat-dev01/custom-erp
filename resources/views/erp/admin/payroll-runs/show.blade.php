@extends('erp::layouts.app')

@section('title', $payrollRun->periodLabel() . ' ' . __('Bordro'))
@section('page-title', $payrollRun->periodLabel() . ' ' . __('Bordro'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex gap-2 mb-3 flex-wrap">
        @if($payrollRun->status === 'processed')
            @can('erp.payroll.approve')
                <form method="POST" action="{{ route('erp.payroll-runs.approve', $payrollRun) }}">
                    @csrf
                    <x-admin-panel::button type="submit" variant="primary" icon="check-circle"
                        onclick="return confirm('{{ __('Bordroyu onaylayıp ödenmiş olarak işaretlemek istediğinize emin misiniz?') }}')">
                        {{ __('Onayla ve Ödenmiş İşaretle') }}
                    </x-admin-panel::button>
                </form>
            @endcan
        @endif
        <x-admin-panel::button href="{{ route('erp.payroll-runs.index') }}" variant="ghost">{{ __('← Liste') }}</x-admin-panel::button>
    </div>

    {{-- Özet kartlar --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Çalışan Sayısı')" :value="$payrollRun->payslips->count()" icon="users" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Toplam Brüt')" :value="$erpFormat->money($payrollRun->total_gross)" icon="banknote" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Toplam Kesinti')" :value="$erpFormat->money($payrollRun->total_deductions)" icon="minus-circle" />
        </div>
        <div class="col-md-3">
            <x-admin-panel::stat-card :label="__('Toplam Net Ödeme')" :value="$erpFormat->money($payrollRun->total_net)" icon="wallet" />
        </div>
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Çalışan'), __('Departman'), __('Brüt Maaş'), __('Kesintiler'), __('Net Maaş'), __('Durum'), '']">
            @forelse($payrollRun->payslips as $payslip)
                <tr>
                    <td>
                        <a href="{{ route('erp.employees.show', $payslip->employee_id) }}">{{ $payslip->employee?->full_name }}</a>
                        <div class="text-muted small">{{ $payslip->employee?->employee_number }}</div>
                    </td>
                    <td>{{ $payslip->employee?->department?->name ?? '-' }}</td>
                    <td>{{ $erpFormat->money($payslip->gross_salary) }}</td>
                    <td>{{ $erpFormat->money($payslip->total_deductions) }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($payslip->net_salary) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ $payslip->status === 'paid' ? 'success' : ($payslip->status === 'approved' ? 'info' : 'secondary') }}">
                            {{ __($payslip->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.payslips.show', $payslip) }}" size="sm" variant="ghost" icon="eye" />
                        <x-admin-panel::button href="{{ route('erp.payslips.pdf', $payslip) }}" size="sm" variant="ghost" icon="download" target="_blank" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('Bordro kalemi bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
@endsection
