@extends('erp::layouts.app')

@section('title', __('Bordro Çalıştırma'))
@section('page-title', __('Bordro Çalıştırma'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.payroll.create')
            <x-admin-panel::button href="{{ route('erp.payroll-runs.create') }}" icon="plus" variant="primary">{{ __('Yeni Bordro Çalıştır') }}</x-admin-panel::button>
        @endcan
    </div>

    <x-admin-panel::card>
        <x-admin-panel::table :headers="[__('Dönem'), __('Çalışan'), __('Brüt Toplam'), __('Kesinti'), __('Net Toplam'), __('Ödeme Tarihi'), __('Durum'), '']">
            @forelse($runs as $run)
                <tr>
                    <td class="fw-medium">{{ $run->periodLabel() }}</td>
                    <td>{{ $run->payslips_count }}</td>
                    <td>{{ $erpFormat->money($run->total_gross) }}</td>
                    <td>{{ $erpFormat->money($run->total_deductions) }}</td>
                    <td class="fw-medium">{{ $erpFormat->money($run->total_net) }}</td>
                    <td>{{ $erpFormat->date($run->pay_date) }}</td>
                    <td>
                        <x-admin-panel::badge variant="{{ match($run->status) { 'paid' => 'success', 'approved' => 'info', 'processed' => 'warning', default => 'secondary' } }}">
                            {{ __($run->status) }}
                        </x-admin-panel::badge>
                    </td>
                    <td class="text-end">
                        <x-admin-panel::button href="{{ route('erp.payroll-runs.show', $run) }}" size="sm" variant="ghost" icon="eye" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ __('Bordro kaydı bulunamadı.') }}</td></tr>
            @endforelse
        </x-admin-panel::table>
    </x-admin-panel::card>
    <div class="mt-3">{{ $runs->links() }}</div>
@endsection
