@extends('erp::layouts.app')

@section('title', __('Banka Hesapları'))
@section('page-title', __('Banka Hesapları'))

@section('content')
    @if(session('success'))
        <x-admin-panel::alert type="success" dismissible>{{ session('success') }}</x-admin-panel::alert>
    @endif

    <div class="d-flex justify-content-end mb-3">
        @can('erp.bank.manage')
            <x-admin-panel::button href="{{ route('erp.bank-accounts.create') }}" icon="plus" variant="primary">{{ __('Yeni Hesap') }}</x-admin-panel::button>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        @forelse($accounts as $item)
            @php $acc = $item['account']; $balance = $item['balance']; @endphp
            <div class="col-md-6 col-xl-4">
                <x-admin-panel::card>
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $acc->name }}</div>
                            <div class="text-muted small">{{ $acc->bank_name }}</div>
                            @if($acc->iban)
                                <div class="text-muted small font-monospace">{{ $acc->iban }}</div>
                            @endif
                        </div>
                        <x-admin-panel::badge variant="secondary">{{ $acc->currency }}</x-admin-panel::badge>
                    </div>
                    <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">{{ __('Güncel Bakiye') }}</div>
                            <div class="fw-bold fs-5 {{ $balance < 0 ? 'text-danger' : '' }}">{{ $erpFormat->money($balance, $acc->currency) }}</div>
                        </div>
                        <x-admin-panel::button href="{{ route('erp.bank-accounts.show', $acc) }}" size="sm" variant="outline" icon="list">
                            {{ __('Hesap Defteri') }}
                        </x-admin-panel::button>
                    </div>
                </x-admin-panel::card>
            </div>
        @empty
            <div class="col-12">
                <x-admin-panel::card>
                    <p class="text-center text-muted py-4 mb-0">{{ __('Henüz banka hesabı eklenmemiş.') }}</p>
                </x-admin-panel::card>
            </div>
        @endforelse
    </div>
@endsection
