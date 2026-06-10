@extends('erp::layouts.app')

@section('title', $bankAccount->name)
@section('page-title', $bankAccount->name)

@section('content')
    @include('erp::admin.partials.status')

    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.bank-accounts.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Hesaplara Dön') }}
        </x-admin-panel::button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Güncel Bakiye')" :value="$erpFormat->money($balance, $bankAccount->currency)" icon="banknote" />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('Para Birimi')" :value="$bankAccount->currency" icon="globe" />
        </div>
        <div class="col-sm-4">
            <x-admin-panel::stat-card :label="__('IBAN')" :value="$bankAccount->iban ?: '-'" icon="credit-card" />
        </div>
    </div>

    <div class="row g-3">
        {{-- İşlem Ekle / Transfer --}}
        <div class="col-md-4">
            @can('erp.bank.manage')
                <x-admin-panel::card class="mb-3">
                    <h6 class="fw-semibold mb-3">{{ __('Yeni İşlem') }}</h6>
                    <form method="POST" action="{{ route('erp.bank-accounts.store-transaction', $bankAccount) }}">
                        @csrf
                        <x-admin-panel::select name="type" :label="__('Tip')" :options="['deposit' => __('Giriş'), 'withdrawal' => __('Çıkış')]" :selected="old('type')" />
                        <x-admin-panel::input name="amount" :label="__('Tutar')" type="number" step="0.01" min="0.01" :value="old('amount')" />
                        <x-admin-panel::input name="transaction_date" :label="__('Tarih')" type="date" :value="old('transaction_date', today()->format('Y-m-d'))" />
                        <x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description')" />
                        <x-admin-panel::input name="reference" :label="__('Referans')" :value="old('reference')" />
                        <x-admin-panel::button type="submit" variant="primary" icon="plus" class="w-100 mt-2">{{ __('Kaydet') }}</x-admin-panel::button>
                    </form>
                </x-admin-panel::card>

                @if($bankAccounts->isNotEmpty())
                    <x-admin-panel::card class="mb-3">
                        <h6 class="fw-semibold mb-3">{{ __('Transfer') }}</h6>
                        <form method="POST" action="{{ route('erp.bank-accounts.transfer', $bankAccount) }}">
                            @csrf
                            <x-admin-panel::select name="to_account_id" :label="__('Hedef Hesap')"
                                :options="$bankAccounts->pluck('name','id')->toArray()" :selected="old('to_account_id')" />
                            <x-admin-panel::input name="amount" :label="__('Tutar')" type="number" step="0.01" min="0.01" :value="old('amount')" />
                            <x-admin-panel::input name="transaction_date" :label="__('Tarih')" type="date" :value="old('transaction_date', today()->format('Y-m-d'))" />
                            <x-admin-panel::input name="description" :label="__('Açıklama')" :value="old('description')" />
                            <x-admin-panel::button type="submit" variant="outline" icon="arrow-right-left" class="w-100 mt-2">{{ __('Transfer') }}</x-admin-panel::button>
                        </form>
                    </x-admin-panel::card>
                @endif

                <x-admin-panel::card>
                    <h6 class="fw-semibold mb-3">{{ __('CSV İçe Aktar') }}</h6>
                    <form method="POST" action="{{ route('erp.bank-accounts.import', $bankAccount) }}" enctype="multipart/form-data">
                        @csrf
                        <p class="text-muted small mb-2">{{ __('Format: tarih, açıklama, tutar (+giriş/-çıkış), referans') }}</p>
                        <input type="file" name="file" accept=".csv,.txt" class="form-control form-control-sm mb-2" required />
                        <x-admin-panel::button type="submit" variant="outline" icon="upload" class="w-100">{{ __('İçe Aktar') }}</x-admin-panel::button>
                    </form>
                </x-admin-panel::card>
            @endcan
        </div>

        {{-- Hareket Listesi --}}
        <div class="col-md-8">
            <x-admin-panel::card>
                <form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
                    <x-admin-panel::input name="date_from" type="date" :value="request('date_from')" />
                    <x-admin-panel::input name="date_to"   type="date" :value="request('date_to')" />
                    <x-admin-panel::button type="submit" variant="outline" icon="search" size="sm">{{ __('Filtrele') }}</x-admin-panel::button>
                </form>

                @can('erp.bank.manage')
                    <form method="POST" action="{{ route('erp.bank-accounts.reconcile', $bankAccount) }}">
                        @csrf
                @endcan

                <x-admin-panel::table :headers="['', __('Tarih'), __('Açıklama'), __('Referans'), __('Giriş'), __('Çıkış'), __('Mutabık')]">
                    @forelse($transactions as $txn)
                        <tr>
                            @can('erp.bank.manage')
                                <td><input type="checkbox" name="ids[]" value="{{ $txn->id }}" {{ $txn->is_reconciled ? 'disabled checked' : '' }} /></td>
                            @else
                                <td></td>
                            @endcan
                            <td>{{ $erpFormat->date($txn->transaction_date) }}</td>
                            <td>{{ $txn->description }}</td>
                            <td class="text-muted small">{{ $txn->reference }}</td>
                            <td class="text-success">{{ $txn->type === 'deposit' ? $erpFormat->money($txn->amount) : '-' }}</td>
                            <td class="text-danger">{{ in_array($txn->type, ['withdrawal']) ? $erpFormat->money($txn->amount) : '-' }}</td>
                            <td>
                                @if($txn->is_reconciled)
                                    <x-admin-panel::badge variant="success">{{ __('Evet') }}</x-admin-panel::badge>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('İşlem bulunamadı.') }}</td></tr>
                    @endforelse
                </x-admin-panel::table>

                @can('erp.bank.manage')
                        <div class="mt-2 d-flex justify-content-end">
                            <x-admin-panel::button type="submit" size="sm" variant="outline" icon="check">{{ __('Seçilenleri Mutabık Say') }}</x-admin-panel::button>
                        </div>
                    </form>
                @endcan
                <div class="mt-3">{{ $transactions->links() }}</div>
            </x-admin-panel::card>
        </div>
    </div>
@endsection
