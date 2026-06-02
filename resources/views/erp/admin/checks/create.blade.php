@extends('erp::layouts.app')

@section('title', __('Yeni Çek/Senet'))
@section('page-title', __('Yeni Çek/Senet'))

@section('content')
    <div class="mb-3">
        <x-admin-panel::button href="{{ route('erp.checks.index') }}" variant="ghost" icon="arrow-left" size="sm">
            {{ __('Portföye Dön') }}
        </x-admin-panel::button>
    </div>

    <form method="POST" action="{{ route('erp.checks.store') }}">
        @csrf
        <x-admin-panel::card>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-admin-panel::select name="type" :label="__('Tip')"
                        :options="['received' => __('Alınan'), 'issued' => __('Verilen')]"
                        :selected="old('type', 'received')" />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="check_number" :label="__('Çek/Senet No')" :value="old('check_number')" required />
                </div>
                <div class="col-md-6">
                    <x-admin-panel::input name="bank_name" :label="__('Banka')" :value="old('bank_name')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="amount" :label="__('Tutar')" type="number" step="0.01" min="0.01" :value="old('amount')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="issue_date" :label="__('Düzenleme Tarihi')" type="date" :value="old('issue_date', today()->format('Y-m-d'))" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::input name="due_date" :label="__('Vade Tarihi')" type="date" :value="old('due_date')" required />
                </div>
                <div class="col-md-3">
                    <x-admin-panel::select name="party_type" :label="__('Taraf Tipi')"
                        :options="['erp_customer' => __('Müşteri'), 'erp_supplier' => __('Tedarikçi')]"
                        :selected="old('party_type', 'erp_customer')" />
                </div>
                <div class="col-md-6" id="customer-select">
                    <x-admin-panel::select name="party_id" :label="__('Müşteri / Tedarikçi')"
                        :options="$customers->pluck('name','id')->toArray()"
                        :selected="old('party_id')" />
                </div>
                <div class="col-12">
                    <x-admin-panel::textarea name="notes" :label="__('Notlar')" :value="old('notes')" rows="2" />
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <x-admin-panel::button href="{{ route('erp.checks.index') }}" variant="outline">{{ __('İptal') }}</x-admin-panel::button>
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
            </div>
        </x-admin-panel::card>
    </form>
@endsection
