@extends('erp::layouts.app')

@section('title', __('Yeni Müşteri'))
@section('page-title', __('Yeni Müşteri'))

@section('content')
    <x-admin-panel::card>
        <form method="POST" action="{{ route('erp.customers.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-8"><x-admin-panel::input name="name" :label="__('Ad / Firma Adı')" :value="old('name')" required /></div>
                <div class="col-md-4">
                    <x-admin-panel::select name="status" :label="__('Durum')"
                        :options="['active' => __('Aktif'), 'inactive' => __('Pasif')]"
                        :selected="old('status', 'active')" />
                </div>
                <div class="col-md-6"><x-admin-panel::input name="email" type="email" :label="__('E-posta')" :value="old('email')" /></div>
                <div class="col-md-6"><x-admin-panel::input name="phone" :label="__('Telefon')" :value="old('phone')" /></div>
                <div class="col-md-6"><x-admin-panel::input name="tax_number" :label="__('Vergi No / TC No')" :value="old('tax_number')" /></div>
                <div class="col-md-6"><x-admin-panel::input name="contact_person" :label="__('İletişim Kişisi')" :value="old('contact_person')" /></div>
                <div class="col-12"><x-admin-panel::input name="address" :label="__('Adres')" :value="old('address')" /></div>
                <div class="col-md-6"><x-admin-panel::input name="payment_terms_days" type="number" :label="__('Ödeme Vadesi (gün)')" :value="old('payment_terms_days', '30')" /></div>
                <div class="col-md-6"><x-admin-panel::input name="credit_limit" type="number" step="0.01" :label="__('Kredi Limiti')" :value="old('credit_limit', '0')" /></div>
            </div>
            @if($errors->any())
                <div class="mt-3"><x-admin-panel::alert type="error"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-admin-panel::alert></div>
            @endif
            <div class="mt-4 d-flex gap-2">
                <x-admin-panel::button type="submit" variant="primary" icon="save">{{ __('Kaydet') }}</x-admin-panel::button>
                <x-admin-panel::button href="{{ route('erp.customers.index') }}" variant="ghost">{{ __('İptal') }}</x-admin-panel::button>
            </div>
        </form>
    </x-admin-panel::card>
@endsection
