@extends('erp::layouts.app')

@section('title', __('Yeni Banka Hesabı'))
@section('page-title', __('Yeni Banka Hesabı'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.bank-accounts.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Hesaplara Dön') }}
        </x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.bank-accounts.store') }}">
        @csrf
        <x-admin-panel::card>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin-panel::input name="name" :label="__('Hesap Adı')" :value="old('name')" placeholder="{{ __('Örn: İş Bankası TL') }}" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="bank_name" :label="__('Banka Adı')" :value="old('bank_name')" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="iban" :label="__('IBAN')" :value="old('iban')" placeholder="TR..." />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="account_number" :label="__('Hesap Numarası')" :value="old('account_number')" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="branch" :label="__('Şube')" :value="old('branch')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::select name="currency" :label="__('Para Birimi')"
                        :options="['TRY' => 'TRY', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP']"
                        :selected="old('currency', 'TRY')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="opening_balance" :label="__('Açılış Bakiyesi')" type="number" step="0.01" :value="old('opening_balance', '0')" />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::select name="account_id" :label="__('Muhasebe Hesabı (opsiyonel)')"
                        :options="$ledgerAccounts->pluck('name','id')->prepend(__('Bağlı Hesap Yok'), '')->map(fn($v,$k) => $k ? $k.' — '.$v : $v)->toArray()"
                        :selected="old('account_id')" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <x-admin-panel::button href="{{ route('erp.bank-accounts.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
            </div>
        </x-admin-panel::card>
    </form>
@endsection
